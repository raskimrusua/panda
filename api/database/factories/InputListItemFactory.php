<?php

namespace Database\Factories;

use App\Models\InputListItem;
use App\Models\Season;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InputListItem>
 */
class InputListItemFactory extends Factory
{
    protected $model = InputListItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $perAcre = fake()->randomFloat(2, 0.5, 100);
        $acreage = fake()->randomFloat(2, 0.5, 5);
        $price = fake()->randomFloat(2, 100, 5000);

        return [
            'tenant_id' => Tenant::factory(),
            'season_id' => Season::factory(),
            'input_type' => fake()->randomElement([
                InputListItem::TYPE_SEED,
                InputListItem::TYPE_FERTILISER,
                InputListItem::TYPE_CHEMICAL,
                InputListItem::TYPE_EQUIPMENT,
                InputListItem::TYPE_OTHER,
            ]),
            'product_name' => fake()->words(2, true),
            'quantity_per_acre' => $perAcre,
            'quantity_scaled' => round($perAcre * $acreage, 4),
            'unit' => fake()->randomElement(['kg', 'g', 'L', 'ml', 'pieces']),
            'week_from_planting' => fake()->numberBetween(-3, 10),
            'benchmark_price_kes' => $price,
            'cost_estimate_kes' => round($price * $acreage, 2),
            'pcpb_registered' => fake()->boolean(30),
            'alternatives' => null,
        ];
    }

    public function seed(): self
    {
        return $this->state(fn () => ['input_type' => InputListItem::TYPE_SEED]);
    }

    public function fertiliser(): self
    {
        return $this->state(fn () => ['input_type' => InputListItem::TYPE_FERTILISER]);
    }

    public function chemical(): self
    {
        return $this->state(fn () => ['input_type' => InputListItem::TYPE_CHEMICAL]);
    }

    public function procured(): self
    {
        return $this->state(fn () => [
            'procured_quantity' => fake()->randomFloat(2, 1, 50),
            'procured_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }
}
