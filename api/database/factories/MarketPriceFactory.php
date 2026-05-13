<?php

namespace Database\Factories;

use App\Models\Crop;
use App\Models\MarketPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketPrice>
 */
class MarketPriceFactory extends Factory
{
    protected $model = MarketPrice::class;

    /** @var list<string> */
    private const MARKETS = [
        'Marikiti (Nairobi)', 'Wakulima (Nairobi)', 'Karatina (Nyeri)',
        'Kongowea (Mombasa)', 'Eldoret (Uasin Gishu)', 'Kibuye (Kisumu)',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'crop_id' => Crop::factory(),
            'market_name' => fake()->randomElement(self::MARKETS),
            'county' => fake()->randomElement(['Nairobi', 'Nyeri', 'Mombasa', 'Uasin Gishu', 'Kisumu']),
            'observed_at' => fake()->dateTimeBetween('-12 months', 'now')->format('Y-m-d'),
            'grade' => 'standard',
            'price_per_kg_kes' => fake()->randomFloat(2, 20, 200),
            'source' => MarketPrice::SOURCE_ADMIN_CSV,
            'notes' => null,
        ];
    }
}
