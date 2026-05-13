<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Kenyan counties most relevant for the JAICA SHEP PLUS pilot
     * (Meru and Kirinyaga lead per pilot plan §14.3).
     *
     * @var list<string>
     */
    private const COUNTIES = [
        'Meru', 'Kirinyaga', 'Nyeri', 'Embu', 'Murang\'a',
        'Machakos', 'Kiambu', 'Nakuru', 'Bungoma', 'Kakamega',
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Farm';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'county' => fake()->randomElement(self::COUNTIES),
            'sub_county' => null,
            'ward' => null,
            'gps_lat' => null,
            'gps_lng' => null,
            'settings' => null,
        ];
    }

    public function meru(): self
    {
        return $this->state(fn () => [
            'county' => 'Meru',
            'sub_county' => 'Imenti North',
        ]);
    }

    public function kirinyaga(): self
    {
        return $this->state(fn () => [
            'county' => 'Kirinyaga',
            'sub_county' => 'Mwea',
        ]);
    }

    public function withGps(): self
    {
        return $this->state(fn () => [
            'gps_lat' => fake()->latitude(-1.5, 0.5),
            'gps_lng' => fake()->longitude(36.5, 38.5),
        ]);
    }
}
