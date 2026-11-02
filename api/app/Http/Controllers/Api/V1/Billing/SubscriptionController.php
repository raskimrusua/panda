<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Billing\InitiateSubscriptionPaymentRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Billing\DarajaStkClient;
use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant-scoped subscription surface: current plan + M-Pesa payment
 * initiation. Mirrors Shira's billing views (plans/current/upgrade/callback).
 */
class SubscriptionController extends Controller
{
    public function show(Request $request): SubscriptionResource
    {
        return new SubscriptionResource($this->currentSubscription($request)->load('plan'));
    }

    public function initiatePayment(InitiateSubscriptionPaymentRequest $request): JsonResponse
    {
        $plan = SubscriptionPlan::query()
            ->where('slug', (string) $request->validated('plan_slug'))
            ->where('is_active', true)
            ->firstOrFail();

        $service = new SubscriptionBillingService(new DarajaStkClient);

        $result = $service->initiatePayment(
            $this->currentSubscription($request),
            (string) $request->validated('phone'),
            $plan,
        );

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function currentSubscription(Request $request): Subscription
    {
        $freePlan = SubscriptionPlan::query()
            ->where('slug', 'mkulima')
            ->where('is_active', true)
            ->first();

        return Subscription::firstOrCreate(
            ['tenant_id' => $request->user()?->tenant_id],
            [
                'plan_id' => $freePlan?->id,
                'status' => Subscription::STATUS_ACTIVE,
                'billing_cycle' => Subscription::BILLING_MONTHLY,
                'current_period_start' => now(),
            ],
        );
    }
}
