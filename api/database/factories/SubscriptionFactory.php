<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'plan_id' => SubscriptionPlan::factory(),
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::BILLING_MONTHLY,
            'current_period_start' => now(),
            'current_period_end' => now()->addDays(30),
        ];
    }

    public function withTenant(Tenant $tenant): static
    {
        return $this->state(['tenant_id' => $tenant->id]);
    }

    public function withPlan(SubscriptionPlan $plan): static
    {
        return $this->state(['plan_id' => $plan->id]);
    }
}
