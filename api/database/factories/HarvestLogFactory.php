<?php

namespace Database\Factories;

use App\Models\HarvestLog;
use App\Models\Season;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HarvestLog>
 */
class HarvestLogFactory extends Factory
{
    protected $model = HarvestLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $picked = fake()->randomFloat(2, 5, 200);
        $sold = $picked * fake()->randomFloat(2, 0.5, 1.0);

        return [
            'tenant_id' => Tenant::factory(),
            'season_id' => Season::factory(),
            'harvested_at' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'quantity_kg' => $picked,
            'sold_quantity_kg' => round($sold, 2),
            'unit_price_kes' => fake()->randomFloat(2, 30, 90),
            'buyer_name' => fake()->boolean(70) ? fake()->company() : null,
            'notes' => null,
            'photo_url' => null,
            'client_id' => null,
            'logged_by' => null,
        ];
    }

    public function unsold(): self
    {
        return $this->state(fn () => [
            'sold_quantity_kg' => 0,
            'unit_price_kes' => null,
            'buyer_name' => null,
        ]);
    }

    public function fullPrice(float $perKg): self
    {
        return $this->state(fn (array $attrs) => [
            'sold_quantity_kg' => $attrs['quantity_kg'],
            'unit_price_kes' => $perKg,
        ]);
    }
}
