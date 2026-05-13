<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HarvestLog — one row per harvest event.
 *
 * Multi-pick crops (tomato, kale, beans) get many rows per Season;
 * single-harvest crops (cabbage, onion) get one. The HarvestLog observer
 * keeps a rolling cumulative + projected revenue stamped on
 * Season.engine_metadata so the season report is fast to render.
 *
 * `quantity_kg` is total picked. `sold_quantity_kg` is what was sold (rest
 * = own consumption / spoilage). `unit_price_kes` is the per-kg sale price
 * for the sold portion. Revenue for the row = sold_quantity_kg *
 * unit_price_kes.
 *
 * `client_id` is the offline-sync idempotency key (matches Shira PWA
 * pattern). Same client_id from a re-sync = same row, never duplicate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvest_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->date('harvested_at');
            $table->decimal('quantity_kg', 12, 2);
            $table->decimal('sold_quantity_kg', 12, 2)->default(0);
            $table->decimal('unit_price_kes', 10, 2)->nullable();
            $table->string('buyer_name', 120)->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->ulid('client_id')->nullable();
            $table->foreignUlid('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'season_id']);
            $table->index(['tenant_id', 'harvested_at']);
            $table->unique(['tenant_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_logs');
    }
};
