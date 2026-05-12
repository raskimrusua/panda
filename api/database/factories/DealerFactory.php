<?php

namespace Database\Factories;

use App\Models\Dealer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Dealer>
 */
class DealerFactory extends Factory
{
    protected $model = Dealer::class;

    /** @var list<string> */
    private const KENYAN_COUNTIES = [
        'Meru', 'Kirinyaga', 'Nyeri', 'Embu', 'Murang\'a',
        'Machakos', 'Kiambu', 'Nakuru', 'Bungoma', 'Kakamega',
        'Nairobi', 'Kisii', 'Trans Nzoia', 'Uasin Gishu',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->company().' Agrovet';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'county' => fake()->randomElement(self::KENYAN_COUNTIES),
            'sub_county' => null,
            'town' => fake()->city(),
            'gps_lat' => fake()->latitude(-1.5, 0.5),
            'gps_lng' => fake()->longitude(34.5, 38.5),
            'phone' => '+2547'.fake()->numerify('########'),
            'whatsapp' => null,
            'website' => null,
            'stocks' => fake()->randomElements([
                Dealer::STOCK_SEED,
                Dealer::STOCK_FERTILISER,
                Dealer::STOCK_CHEMICAL,
                Dealer::STOCK_EQUIPMENT,
            ], fake()->numberBetween(2, 4)),
            'is_pcpb_certified' => fake()->boolean(60),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inMeru(): self
    {
        return $this->state(fn () => [
            'county' => 'Meru',
            'gps_lat' => 0.05,
            'gps_lng' => 37.65,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
