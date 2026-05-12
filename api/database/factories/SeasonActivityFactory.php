<?php

namespace Database\Factories;

use App\Models\Season;
use App\Models\SeasonActivity;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SeasonActivity>
 */
class SeasonActivityFactory extends Factory
{
    protected $model = SeasonActivity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $week = fake()->numberBetween(-3, 12);

        return [
            'tenant_id' => Tenant::factory(),
            'season_id' => Season::factory(),
            'activity_type' => fake()->randomElement([
                'nursery_seeding', 'transplanting', 'basal_fertiliser',
                'top_dress', 'pesticide_spray', 'harvest_pick',
            ]),
            'phase' => fake()->randomElement(['nursery', 'transplanting', 'vegetative', 'flowering', 'harvest']),
            'ideal_date' => now()->addWeeks($week)->toDateString(),
            'week_from_planting' => $week,
            'day_window' => fake()->numberBetween(0, 5),
            'description_en' => fake()->sentence(),
            'description_sw' => fake()->sentence(),
            'tip_en' => null,
            'tip_sw' => null,
            'is_critical' => fake()->boolean(40),
            'status' => SeasonActivity::STATUS_PENDING,
        ];
    }

    public function done(): self
    {
        return $this->state(fn () => [
            'status' => SeasonActivity::STATUS_DONE,
            'completed_at' => now(),
        ]);
    }

    public function overdue(): self
    {
        return $this->state(fn () => [
            'status' => SeasonActivity::STATUS_OVERDUE,
            'ideal_date' => now()->subWeeks(2)->toDateString(),
        ]);
    }

    public function critical(): self
    {
        return $this->state(fn () => ['is_critical' => true]);
    }
}
