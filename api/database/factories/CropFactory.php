<?php

namespace Database\Factories;

use App\Models\Crop;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Crop>
 */
class CropFactory extends Factory
{
    protected $model = Crop::class;

    /**
     * Anonymous crop. Use named states for the 17 JICA SHEP PLUS crops.
     */
    public function definition(): array
    {
        $slug = $this->faker->unique()->slug(2);

        return [
            'slug' => $slug,
            'name_en' => ucfirst($this->faker->word()),
            'name_sw' => ucfirst($this->faker->word()),
            'category' => $this->faker->randomElement(['vegetable', 'fruit', 'leafy_green', 'legume', 'staple', 'tuber']),
            'harvest_type' => $this->faker->randomElement(['single', 'multi']),
            'image_url' => null,
            'jica_manual_ref' => null,
            'phase_added' => 1,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function phase2(): static
    {
        return $this->state(['phase_added' => 2]);
    }

    // Named-state helpers for the 17 JICA SHEP PLUS Phase-1 crops.
    // Slug + name pairs are canonical (used by ContentLoader to resolve content).

    public function tomato(): static
    {
        return $this->state([
            'slug' => 'tomato',
            'name_en' => 'Tomato',
            'name_sw' => 'Nyanya',
            'category' => 'vegetable',
            'harvest_type' => 'multi',
            'phase_added' => 1,
        ]);
    }

    public function kale(): static
    {
        return $this->state([
            'slug' => 'kale',
            'name_en' => 'Kale (Sukuma Wiki)',
            'name_sw' => 'Sukuma Wiki',
            'category' => 'leafy_green',
            'harvest_type' => 'multi',
            'phase_added' => 1,
        ]);
    }

    public function cabbage(): static
    {
        return $this->state([
            'slug' => 'cabbage',
            'name_en' => 'Cabbage',
            'name_sw' => 'Kabichi',
            'category' => 'vegetable',
            'harvest_type' => 'single',
            'phase_added' => 1,
        ]);
    }

    public function bulbOnion(): static
    {
        return $this->state([
            'slug' => 'bulb-onion',
            'name_en' => 'Bulb Onion',
            'name_sw' => 'Kitunguu',
            'category' => 'tuber',
            'harvest_type' => 'single',
            'phase_added' => 1,
        ]);
    }

    public function frenchBeans(): static
    {
        return $this->state([
            'slug' => 'french-beans',
            'name_en' => 'French Beans',
            'name_sw' => 'Maharagwe ya mkono',
            'category' => 'legume',
            'harvest_type' => 'multi',
            'phase_added' => 1,
        ]);
    }
}
