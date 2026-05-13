<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dealer — agro-input shop directory. NOT tenant-scoped — every farm sees
 * the same dealer catalogue. Read is public; writes are admin-only via
 * Filament.
 *
 * `stocks` is a JSON array of category strings ('seed', 'fertiliser',
 * 'chemical', 'equipment') so the GET /dealers endpoint can filter.
 *
 * GPS columns mandatory because the search-by-radius use case is the
 * point of this table — a dealer with no GPS can't be found that way.
 *
 * Distance search uses haversine in the controller (works on both
 * Postgres and SQLite). PostGIS would be faster at scale but P4-pilot
 * volume (30-50 dealers) doesn't justify the complexity yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dealers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name', 200);
            $table->string('slug', 80)->unique();
            $table->string('county', 64);
            $table->string('sub_county', 64)->nullable();
            $table->string('town', 80)->nullable();
            $table->decimal('gps_lat', 9, 6);
            $table->decimal('gps_lng', 9, 6);
            $table->string('phone', 32)->nullable();
            $table->string('whatsapp', 32)->nullable();
            $table->string('website', 200)->nullable();
            $table->json('stocks');
            $table->boolean('is_pcpb_certified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('county');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
