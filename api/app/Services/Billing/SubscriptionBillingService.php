<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * SubscriptionBillingService — subscription payment initiation + STK callback
 * processing. Ported case-for-case from Shira's PlatformBillingService.
 *
 * Key Shira behaviours preserved:
 *  - Free plan short-circuits before any Daraja call.
 *  - Phone normalized to 254XXXXXXXXX before the push.
 *  - Pending payment row is created first; failed token/push marks it failed.
 *  - Callback always completes without throwing (Safaricom retries non-2xx);
 *    idempotent on completed/failed payments.
 *  - Plan upgrades: payment.notes = "upgrade_to:<plan_id>" — applied inside
 *    the same DB transaction that completes the payment.
 */
class SubscriptionBillingService
{
    public function __construct(private readonly DarajaStkClient $daraja) {}

    /** Normalize a Kenyan phone to 254XXXXXXXXX (12 digits) or null. */
    public static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '254') && strlen($digits) === 12) {
            return $digits;
        }

        if (str_starts_with($digits, '0') && strlen($digits) === 10) {
            return '254'.substr($digits, 1);
        }

        return null;
    }

    /**
     * Send an STK push for a subscription payment.
     *
     * @return array{success: bool, message: string, payment_id?: string, checkout_request_id?: string|null, plan?: array{slug: string, name: string}}
     */
    public function initiatePayment(Subscription $subscription, string $phone, ?SubscriptionPlan $targetPlan = null): array
    {
        $plan = $targetPlan ?? $subscription->plan;
        if ($plan === null) {
            return ['success' => false, 'message' => 'No plan selected.'];
        }

        if ($plan->isFree()) {
            $subscription->update([
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_ACTIVE,
                'cancelled_at' => null,
            ]);

            return [
                'success' => true,
                'message' => 'Free plan — no payment required.',
                'plan' => ['slug' => $plan->slug, 'name' => $plan->name],
            ];
        }

        $normalizedPhone = self::normalizePhone($phone);
        if ($normalizedPhone === null) {
            return ['success' => false, 'message' => 'Invalid phone number format.'];
        }

        $isUpgrade = $targetPlan !== null && $subscription->plan_id !== $targetPlan->id;

        $payment = SubscriptionPayment::create([
            'subscription_id' => $subscription->id,
            'amount' => $plan->price_kes,
            'currency' => 'KES',
            'payment_method' => SubscriptionPayment::PAYMENT_MPESA,
            'phone' => $normalizedPhone,
            'status' => SubscriptionPayment::STATUS_PENDING,
            'notes' => $isUpgrade ? 'upgrade_to:'.$plan->id : null,
        ]);

        $token = $this->daraja->token();
        if ($token === null) {
            $payment->update(['status' => SubscriptionPayment::STATUS_FAILED, 'notes' => 'Failed to obtain M-Pesa access token']);

            return ['success' => false, 'message' => 'Payment service unavailable.'];
        }

        $result = $this->daraja->stkPush(
            $token,
            $normalizedPhone,
            $plan->price_kes,
            (string) $payment->id,
            'Panda '.$plan->name.' Subscription',
        );

        if ($result['success']) {
            $payment->update(['checkout_request_id' => $result['checkout_request_id'] ?? null]);

            return [
                'success' => true,
                'message' => 'Payment request sent. Check your phone.',
                'payment_id' => (string) $payment->id,
                'checkout_request_id' => (string) ($result['checkout_request_id'] ?? ''),
            ];
        }

        $payment->update([
            'status' => SubscriptionPayment::STATUS_FAILED,
            'notes' => $result['message'] ?? 'STK Push failed',
        ]);

        return ['success' => false, 'message' => $result['message'] ?? 'Payment failed.'];
    }

    /**
     * Process an STK callback payload. Never throws — callers always 200.
     *
     * @param  array<string, mixed>  $payload
     */
    public function processStkCallback(array $payload): void
    {
        try {
            $stkCallback = $payload['Body']['stkCallback'] ?? [];
            $resultCode = $stkCallback['ResultCode'] ?? null;
            $checkoutId = $stkCallback['CheckoutRequestID'] ?? null;

            if (! is_string($checkoutId) || $checkoutId === '') {
                Log::warning('[billing] Callback missing CheckoutRequestID');

                return;
            }

            $payment = SubscriptionPayment::query()
                ->with('subscription.plan')
                ->where('checkout_request_id', $checkoutId)
                ->first();

            if ($payment === null) {
                Log::warning('[billing] No payment for CheckoutRequestID: '.$checkoutId);

                return;
            }

            // Idempotency — Safaricom may re-deliver the same callback.
            if (in_array($payment->status, [SubscriptionPayment::STATUS_COMPLETED, SubscriptionPayment::STATUS_FAILED], true)) {
                Log::info('[billing] Payment '.$payment->id.' already '.$payment->status.', skipping');

                return;
            }

            $payment->update(['result_json' => $stkCallback]);

            if ($resultCode === 0) {
                $receipt = '';
                $transactionDate = '';
                foreach ($stkCallback['CallbackMetadata']['Item'] ?? [] as $item) {
                    $name = $item['Name'] ?? '';
                    if ($name === 'MpesaReceiptNumber') {
                        $receipt = (string) ($item['Value'] ?? '');
                    } elseif ($name === 'TransactionDate') {
                        $transactionDate = (string) ($item['Value'] ?? '');
                    }
                }

                DB::transaction(function () use ($payment, $receipt, $transactionDate): void {
                    $payment->update([
                        'status' => SubscriptionPayment::STATUS_COMPLETED,
                        'receipt_number' => $receipt,
                        'transaction_id' => $transactionDate !== '' ? $transactionDate : null,
                        'paid_at' => now(),
                    ]);

                    $subscription = $payment->subscription;

                    // Pending plan upgrade — applied atomically with the payment.
                    if (is_string($payment->notes) && str_starts_with($payment->notes, 'upgrade_to:')) {
                        $targetPlan = SubscriptionPlan::find(substr($payment->notes, 11));
                        if ($targetPlan !== null) {
                            $subscription->plan_id = $targetPlan->id;
                        } else {
                            Log::error('[billing] Upgrade target plan missing: '.$payment->notes);
                        }
                    }

                    $this->activate($subscription, $subscription->billing_cycle ?? Subscription::BILLING_MONTHLY);
                });

                Log::info('[billing] Subscription payment completed: '.$payment->id);
            } else {
                $resultDesc = (string) ($stkCallback['ResultDesc'] ?? 'Unknown error');
                $payment->update([
                    'status' => SubscriptionPayment::STATUS_FAILED,
                    'notes' => 'M-Pesa error: '.$resultDesc,
                ]);
                Log::warning('[billing] Subscription payment failed: '.$payment->id.' — '.$resultDesc);
            }
        } catch (Throwable $e) {
            Log::error('[billing] Error processing M-Pesa callback: '.$e->getMessage());
        }
    }

    private function activate(Subscription $subscription, string $billingCycle): void
    {
        $days = $billingCycle === Subscription::BILLING_YEARLY ? 365 : 30;

        $subscription->update([
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays($days),
            'cancelled_at' => null,
        ]);
    }
}
