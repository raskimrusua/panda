<?php

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\Billing\DarajaStkClient;
use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'daraja.consumer_key' => 'ck',
        'daraja.consumer_secret' => 'cs',
        'daraja.shortcode' => '174379',
        'daraja.passkey' => 'test-pass',
        'daraja.subscription_callback_url' => 'https://example.test/billing/callback',
    ]);
});

/*
| Subscription billing service — M-Pesa STK initiation + callback processing.
| Ported from Shira's tests/test_billing.py (PlatformBillingService cases).
*/

function makeBillingService(): SubscriptionBillingService
{
    return new SubscriptionBillingService(new DarajaStkClient);
}

function makePaidSubscription(string $slug = 'paid-test', int $priceKes = 200): Subscription
{
    $tenant = Tenant::factory()->create();
    $plan = SubscriptionPlan::factory()->create(['slug' => $slug, 'price_kes' => $priceKes]);

    return Subscription::factory()->withTenant($tenant)->withPlan($plan)->create();
}

it('normalizes Kenyan phone numbers to 254XXXXXXXXX', function (string $input, ?string $expected) {
    expect(SubscriptionBillingService::normalizePhone($input))->toBe($expected);
})->with([
    ['0712345678', '254712345678'],
    ['+254712345678', '254712345678'],
    ['254 712 345 678', '254712345678'],
    ['0112345678', '254112345678'],
    ['12345', null],
    ['+15551234567', null],
]);

it('short-circuits free plans without any Daraja call', function () {
    Http::preventStrayRequests();

    $tenant = Tenant::factory()->create();
    $freePlan = SubscriptionPlan::factory()->free()->create();
    $subscription = Subscription::factory()->withTenant($tenant)->withPlan($freePlan)->create();

    $result = makeBillingService()->initiatePayment($subscription, '0712345678', $freePlan);

    expect($result['success'])->toBeTrue()
        ->and(SubscriptionPayment::count())->toBe(0);
});

it('rejects an invalid phone before creating a payment', function () {
    Http::preventStrayRequests();

    $tenant = Tenant::factory()->create();
    $plan = SubscriptionPlan::factory()->create(['price_kes' => 200]);
    $subscription = Subscription::factory()->withTenant($tenant)->withPlan($plan)->create();

    $result = makeBillingService()->initiatePayment($subscription, 'not-a-phone', $plan);

    expect($result['success'])->toBeFalse()
        ->and(SubscriptionPayment::count())->toBe(0);
});

it('marks the payment failed when the Daraja token cannot be obtained', function () {
    Http::preventStrayRequests();
    Http::fake([
        'sandbox.safaricom.co.ke/*' => Http::response(['error' => 'invalid credentials'], 401),
    ]);

    $subscription = makePaidSubscription();

    $result = makeBillingService()->initiatePayment($subscription, '0712345678');

    expect($result['success'])->toBeFalse()
        ->and(SubscriptionPayment::count())->toBe(1)
        ->and(SubscriptionPayment::first()->status)->toBe(SubscriptionPayment::STATUS_FAILED);
});

it('creates a pending payment with the checkout id on a successful STK push', function () {
    Http::preventStrayRequests();
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'test-token']),
        'sandbox.safaricom.co.ke/mpesa/*' => Http::response(['ResponseCode' => '0', 'CheckoutRequestID' => 'ws_CO_123']),
    ]);

    $subscription = makePaidSubscription();

    $result = makeBillingService()->initiatePayment($subscription, '0712345678');

    expect($result['success'])->toBeTrue()
        ->and($result['checkout_request_id'])->toBe('ws_CO_123');

    $payment = SubscriptionPayment::first();
    expect($payment->status)->toBe(SubscriptionPayment::STATUS_PENDING)
        ->and($payment->phone)->toBe('254712345678')
        ->and($payment->amount)->toBe(200);
});

it('marks the payment failed when the STK push API rejects the request', function () {
    Http::preventStrayRequests();
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'test-token']),
        'sandbox.safaricom.co.ke/mpesa/*' => Http::response(['ResponseCode' => '1', 'ResponseDescription' => 'Bad shortcode'], 500),
    ]);

    $subscription = makePaidSubscription();

    $result = makeBillingService()->initiatePayment($subscription, '0712345678');

    expect($result['success'])->toBeFalse()
        ->and(SubscriptionPayment::first()->status)->toBe(SubscriptionPayment::STATUS_FAILED);
});

it('completes the payment and activates the subscription on a success callback', function () {
    $subscription = makePaidSubscription();
    $payment = SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription->id,
        'tenant_id' => $subscription->tenant_id,
        'amount' => 200,
        'status' => SubscriptionPayment::STATUS_PENDING,
        'checkout_request_id' => 'ws_CO_CB_OK',
    ]);

    makeBillingService()->processStkCallback([
        'Body' => ['stkCallback' => [
            'ResultCode' => 0,
            'ResultDesc' => 'The service request is processed successfully.',
            'CheckoutRequestID' => 'ws_CO_CB_OK',
            'CallbackMetadata' => ['Item' => [
                ['Name' => 'Amount', 'Value' => 200],
                ['Name' => 'MpesaReceiptNumber', 'Value' => 'RCT123ABC'],
                ['Name' => 'TransactionDate', 'Value' => 20261102123456],
                ['Name' => 'PhoneNumber', 'Value' => 254712345678],
            ]],
        ]],
    ]);

    $payment->refresh();
    $subscription->refresh();

    expect($payment->status)->toBe(SubscriptionPayment::STATUS_COMPLETED)
        ->and($payment->receipt_number)->toBe('RCT123ABC')
        ->and($payment->paid_at)->not->toBeNull()
        ->and($subscription->status)->toBe(Subscription::STATUS_ACTIVE)
        ->and($subscription->current_period_end)->not->toBeNull();
});

it('applies a pending plan upgrade atomically with the payment', function () {
    $subscription = makePaidSubscription();
    $boma = SubscriptionPlan::factory()->create(['slug' => 'boma-test', 'price_kes' => 600]);
    $payment = SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription->id,
        'tenant_id' => $subscription->tenant_id,
        'amount' => 600,
        'status' => SubscriptionPayment::STATUS_PENDING,
        'checkout_request_id' => 'ws_CO_UPGRADE',
        'notes' => 'upgrade_to:'.$boma->id,
    ]);

    makeBillingService()->processStkCallback([
        'Body' => ['stkCallback' => [
            'ResultCode' => 0,
            'CheckoutRequestID' => 'ws_CO_UPGRADE',
            'CallbackMetadata' => ['Item' => [
                ['Name' => 'MpesaReceiptNumber', 'Value' => 'RCT999'],
            ]],
        ]],
    ]);

    $subscription->refresh();
    expect($subscription->plan_id)->toBe($boma->id);
});

it('marks the payment failed on a non-zero result code', function () {
    $subscription = makePaidSubscription();
    $payment = SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription->id,
        'tenant_id' => $subscription->tenant_id,
        'status' => SubscriptionPayment::STATUS_PENDING,
        'checkout_request_id' => 'ws_CO_FAIL',
    ]);

    makeBillingService()->processStkCallback([
        'Body' => ['stkCallback' => [
            'ResultCode' => 1032,
            'ResultDesc' => 'Request cancelled by user',
            'CheckoutRequestID' => 'ws_CO_FAIL',
        ]],
    ]);

    $payment->refresh();
    expect($payment->status)->toBe(SubscriptionPayment::STATUS_FAILED)
        ->and($payment->notes)->toContain('cancelled');
});

it('is idempotent — a re-delivered callback never re-processes a completed payment', function () {
    $subscription = makePaidSubscription();
    $payment = SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription->id,
        'tenant_id' => $subscription->tenant_id,
        'status' => SubscriptionPayment::STATUS_COMPLETED,
        'checkout_request_id' => 'ws_CO_DUP',
        'receipt_number' => 'RCTORIGINAL',
        'paid_at' => now()->subDay(),
    ]);

    makeBillingService()->processStkCallback([
        'Body' => ['stkCallback' => [
            'ResultCode' => 0,
            'CheckoutRequestID' => 'ws_CO_DUP',
            'CallbackMetadata' => ['Item' => [['Name' => 'MpesaReceiptNumber', 'Value' => 'RCTFAKE']]],
        ]],
    ]);

    $payment->refresh();
    expect($payment->receipt_number)->toBe('RCTORIGINAL');
});

it('swallows callbacks with unknown checkout ids', function () {
    makeBillingService()->processStkCallback([
        'Body' => ['stkCallback' => ['ResultCode' => 0, 'CheckoutRequestID' => 'ws_CO_UNKNOWN']],
    ]);

    expect(SubscriptionPayment::count())->toBe(0);
});
