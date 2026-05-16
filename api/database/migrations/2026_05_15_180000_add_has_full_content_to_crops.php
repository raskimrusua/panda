<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `has_full_content` gates season planning to crops whose JSON content
 * has been authored AND signed off by the agronomist (Silas).
 *
 * Workflow:
 *   1. Author drafts a crop JSON in resources/content/crops/<slug>.json
 *      — schema-valid but possibly imperfect on disease/spray windows.
 *   2. Catalogue row seeded with has_full_content = false → the crop
 *      appears in the public list with a "Coming soon" badge but is
 *      disabled in the New Season form.
 *   3. Agronomist opens the Filament Crop admin, reads the JSON,
 *      cross-checks against KALRO/MoALF/SHEP PLUS field practice,
 *      flips the toggle.
 *   4. Crop becomes plantable for farmers.
 *
 * Tomato (only crop with a Silas-equivalent body of public KALRO
 * documentation in the seed) is backfilled to true here so existing
 * test fixtures continue to work.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->boolean('has_full_content')->default(false)->after('is_active');
        });

        DB::table('crops')->where('slug', 'tomato')->update(['has_full_content' => true]);
    }

    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn('has_full_content');
        });
    }
};
