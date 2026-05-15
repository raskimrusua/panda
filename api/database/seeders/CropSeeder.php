<?php

namespace Database\Seeders;

use App\Models\Crop;
use Illuminate\Database\Seeder;

/**
 * Seeds the 17-crop SHEP PLUS catalogue. `has_full_content` defaults to
 * false; flipped to true per crop after the agronomist (Silas) reviews
 * the JSON in resources/content/crops/<slug>.json.
 *
 * Tomato is the only crop bootstrapped with content review at seed time
 * (see add_has_full_content_to_crops migration); the other 16 ship as
 * "Coming soon" until reviewed.
 *
 * Idempotent — uses updateOrCreate on slug so re-running picks up
 * renamed crops or refreshed bilingual labels without duplicating rows.
 */
class CropSeeder extends Seeder
{
    public function run(): void
    {
        $crops = [
            // ---- Phase 1: original JAICA MVP 5 ----
            ['slug' => 'tomato',         'name_en' => 'Tomato',         'name_sw' => 'Nyanya',       'category' => 'vegetable',   'harvest_type' => 'multi',  'phase_added' => 1, 'has_full_content' => true],
            ['slug' => 'kale',           'name_en' => 'Kale',           'name_sw' => 'Sukuma wiki',  'category' => 'leafy_green', 'harvest_type' => 'multi',  'phase_added' => 1],
            ['slug' => 'cabbage',        'name_en' => 'Cabbage',        'name_sw' => 'Kabichi',      'category' => 'vegetable',   'harvest_type' => 'single', 'phase_added' => 1],
            ['slug' => 'bulb-onion',     'name_en' => 'Bulb onion',     'name_sw' => 'Kitunguu',     'category' => 'vegetable',   'harvest_type' => 'single', 'phase_added' => 1],
            ['slug' => 'french-beans',   'name_en' => 'French beans',   'name_sw' => 'Mishiri',      'category' => 'legume',      'harvest_type' => 'multi',  'phase_added' => 1],

            // ---- Phase 2: SHEP PLUS high-value horticulture + indigenous leafy greens ----
            ['slug' => 'capsicum',       'name_en' => 'Capsicum',       'name_sw' => 'Pilipili hoho', 'category' => 'vegetable',   'harvest_type' => 'multi',  'phase_added' => 2],
            ['slug' => 'chili',          'name_en' => 'Chili',          'name_sw' => 'Pilipili',     'category' => 'vegetable',   'harvest_type' => 'multi',  'phase_added' => 2],
            ['slug' => 'eggplant',       'name_en' => 'Eggplant',       'name_sw' => 'Biringanya',   'category' => 'vegetable',   'harvest_type' => 'multi',  'phase_added' => 2],
            ['slug' => 'potato',         'name_en' => 'Irish potato',   'name_sw' => 'Viazi',        'category' => 'tuber',       'harvest_type' => 'single', 'phase_added' => 2],
            ['slug' => 'watermelon',     'name_en' => 'Watermelon',     'name_sw' => 'Tikiti maji',  'category' => 'fruit',       'harvest_type' => 'single', 'phase_added' => 2],
            ['slug' => 'amaranthus',     'name_en' => 'Amaranthus',     'name_sw' => 'Mchicha',      'category' => 'leafy_green', 'harvest_type' => 'multi',  'phase_added' => 2],
            ['slug' => 'black-nightshade', 'name_en' => 'Black nightshade', 'name_sw' => 'Managu',     'category' => 'leafy_green', 'harvest_type' => 'multi',  'phase_added' => 2],
            ['slug' => 'cowpea-leaves',  'name_en' => 'Cowpea leaves',  'name_sw' => 'Kunde',        'category' => 'leafy_green', 'harvest_type' => 'multi',  'phase_added' => 2],

            // ---- Phase 3: perennials (fruit trees + passion) ----
            ['slug' => 'avocado',        'name_en' => 'Avocado',        'name_sw' => 'Parachichi',   'category' => 'fruit',       'harvest_type' => 'multi',  'phase_added' => 3],
            ['slug' => 'banana',         'name_en' => 'Banana',         'name_sw' => 'Ndizi',        'category' => 'fruit',       'harvest_type' => 'multi',  'phase_added' => 3],
            ['slug' => 'mango',          'name_en' => 'Mango',          'name_sw' => 'Embe',         'category' => 'fruit',       'harvest_type' => 'multi',  'phase_added' => 3],
            ['slug' => 'passion-fruit',  'name_en' => 'Passion fruit',  'name_sw' => 'Pasheni',      'category' => 'fruit',       'harvest_type' => 'multi',  'phase_added' => 3],
        ];

        foreach ($crops as $row) {
            Crop::updateOrCreate(['slug' => $row['slug']], array_merge([
                'is_active' => true,
                'has_full_content' => false,
                'jica_manual_ref' => 'Inspired by JICA SHEP PLUS Kenya horticulture curriculum',
            ], $row));
        }
    }
}
