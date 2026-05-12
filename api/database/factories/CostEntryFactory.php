<?php

namespace Database\Factories;

use App\Models\CostEntry;
use App\Models\Season;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostEntry>
 */
class CostEntryFactory extends Factory
{
    protected $model = CostEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'season_id' => Season::factory(),
            'input_list_item_id' => null,
            'category' => fake()->randomElement([
                CostEntry::CATEGORY_SEED,
                CostEntry::CATEGORY_FERTILISER,
                CostEntry::CATEGORY_CHEMICAL,
                CostEntry::CATEGORY_LABOUR,
                CostEntry::CATEGORY_OTHER,
            ]),
            'description' => fake()->sentence(3),
            'amount_kes' => fake()->randomFloat(2, 100, 10_000),
            'incurred_at' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'supplier_name' => fake()->boolean(70) ? fake()->company() : null,
            'receipt_url' => null,
            'logged_by' => null,
        ];
    }

    public function seed(): self
    {
        return $this->state(fn () => ['category' => CostEntry::CATEGORY_SEED]);
    }

    public function labour(): self
    {
        return $this->state(fn () => ['category' => CostEntry::CATEGORY_LABOUR]);
    }
}
