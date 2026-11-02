<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<SubscriptionPlan> */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => fn (array $attributes) => Str::slug($attributes['name']),
            'price_kes' => fake()->numberBetween(100, 1000),
            'billing_cycle' => SubscriptionPlan::BILLING_MONTHLY,
            'features' => [],
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function free(): static
    {
        return $this->state(['price_kes' => 0, 'slug' => 'mkulima', 'name' => 'Mkulima']);
    }
}
