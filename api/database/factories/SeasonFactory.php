<?php

namespace Database\Factories;

use App\Models\Crop;
use App\Models\Season;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    protected $model = Season::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'crop_id' => Crop::factory(),
            'acreage' => fake()->randomFloat(2, 0.25, 5),
            'planting_date' => fake()->dateTimeBetween('-30 days', '+30 days'),
            'status' => Season::STATUS_ACTIVE,
            'irrigation_type' => Season::IRRIGATION_RAINFED,
            'engine_metadata' => null,
            'client_id' => null,
        ];
    }

    public function planning(): self
    {
        return $this->state(fn () => ['status' => Season::STATUS_PLANNING]);
    }

    public function harvesting(): self
    {
        return $this->state(fn () => ['status' => Season::STATUS_HARVESTING]);
    }

    public function complete(): self
    {
        return $this->state(fn () => ['status' => Season::STATUS_COMPLETE]);
    }

    public function greenhouse(): self
    {
        return $this->state(fn () => ['irrigation_type' => Season::IRRIGATION_GREENHOUSE]);
    }
}
