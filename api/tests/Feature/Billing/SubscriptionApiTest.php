<?php

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/*
| Billing API surface — plan catalogue, subscription show, payment
| initiation, and the public Safaricom callback sink.
*/

function billingFarmer(): array
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    test()->actingAs($user);

    return [$tenant, $user];
}

it('lists the seeded plans publicly', function () {
    $this->getJson('/api/v1/billing/plans')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.slug', 'mkulima')
        ->assertJsonPath('data.1.price_kes', 200)
        ->assertJsonPath('data.2.slug', 'boma');
});

it('creates a default free subscription for a tenant without one', function () {
    billingFarmer();

    $this->getJson('/api/v1/billing/subscription')
        ->assertOk()
        ->assertJsonPath('data.plan.slug', 'mkulima')
        ->assertJsonPath('data.status', 'active');

    expect(Subscription::count())->toBe(1);
});

it('requires auth for the subscription endpoint', function () {
    $this->getJson('/api/v1/billing/subscription')->assertUnauthorized();
});

it('validates the initiate-payment body', function () {
    billingFarmer();

    $this->postJson('/api/v1/billing/subscription/initiate-payment', [
        'phone' => '0712345678',
        'plan_slug' => 'not-a-plan',
    ])->assertUnprocessable()->assertJsonValidationErrors(['plan_slug']);
});

it('initiates an STK push for a paid plan', function () {
    Http::preventStrayRequests();
    Http::fake([
        'sandbox.safaricom.co.ke/oauth/*' => Http::response(['access_token' => 'test-token']),
        'sandbox.safaricom.co.ke/mpesa/*' => Http::response(['ResponseCode' => '0', 'CheckoutRequestID' => 'ws_CO_API']),
    ]);

    billingFarmer();

    $this->postJson('/api/v1/billing/subscription/initiate-payment', [
        'phone' => '0712345678',
        'plan_slug' => 'shamba',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('checkout_request_id', 'ws_CO_API');

    expect(SubscriptionPayment::count())->toBe(1)
        ->and(SubscriptionPayment::first()->status)->toBe(SubscriptionPayment::STATUS_PENDING);
});

it('returns 422 when the payment service is unavailable', function () {
    Http::preventStrayRequests();
    Http::fake([
        'sandbox.safaricom.co.ke/*' => Http::response(['error' => 'no creds'], 401),
    ]);

    billingFarmer();

    $this->postJson('/api/v1/billing/subscription/initiate-payment', [
        'phone' => '0712345678',
        'plan_slug' => 'shamba',
    ])->assertUnprocessable()->assertJsonPath('success', false);
});

it('accepts the Safaricom callback without auth and completes the payment', function () {
    [$tenant] = billingFarmer();
    $subscription = Subscription::factory()->withTenant($tenant)->create();
    $payment = SubscriptionPayment::factory()->create([
        'subscription_id' => $subscription->id,
        'tenant_id' => $tenant->id,
        'status' => SubscriptionPayment::STATUS_PENDING,
        'checkout_request_id' => 'ws_CO_CB_API',
    ]);

    $this->postJson('/api/v1/billing/callback', [
        'Body' => ['stkCallback' => [
            'ResultCode' => 0,
            'CheckoutRequestID' => 'ws_CO_CB_API',
            'CallbackMetadata' => ['Item' => [
                ['Name' => 'MpesaReceiptNumber', 'Value' => 'RCTCB1'],
            ]],
        ]],
    ])->assertOk()->assertJsonPath('ResultCode', 0);

    $payment->refresh();
    expect($payment->status)->toBe(SubscriptionPayment::STATUS_COMPLETED)
        ->and($payment->receipt_number)->toBe('RCTCB1');
});

it('always returns 200 on the callback even for garbage payloads', function () {
    $this->postJson('/api/v1/billing/callback', ['garbage' => true])
        ->assertOk()
        ->assertJsonPath('ResultCode', 0);
});
