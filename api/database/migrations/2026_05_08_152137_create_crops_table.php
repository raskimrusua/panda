<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crops', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Identity — slug is the link to resources/content/crops/<slug>.json
            $table->string('slug', 64)->unique();
            $table->string('name_en', 120);
            $table->string('name_sw', 120);

            // Taxonomy: vegetable | fruit | leafy_green | legume | staple | tuber
            $table->string('category', 32);
            // single | multi
            $table->string('harvest_type', 16);

            // Optional metadata
            $table->string('image_url', 500)->nullable();
            $table->string('jica_manual_ref', 500)->nullable();
            // Build phase the crop was added in (1 = JAICA MVP 5 crops, 2 = next 12)
            $table->unsignedTinyInteger('phase_added')->default(1);

            // Lifecycle
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            // Common queries: list active crops, filter by category/harvest_type/phase
            $table->index(['is_active', 'category']);
            $table->index('harvest_type');
            $table->index('phase_added');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crops');
    }
};
