<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MarketPrice — historical + current commodity prices at named markets.
 * NOT tenant-scoped — public catalogue, admin-managed via CSV import.
 *
 * One row per (crop, market, observed_at, grade). The same market can
 * have an A1-grade and a B-grade price for the same crop on the same day.
 *
 * `source` distinguishes manually-entered (`admin_csv`) from scraped
 * (`amis_kenya` etc.) — useful for trust scoring later.
 *
 * Forecast + off-season-opportunity endpoints aggregate over this table;
 * no separate forecast table for v1 (rule-based 3-yr seasonal averages
 * computed at query time — small data, fine performance).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_prices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->string('market_name', 120);
            $table->string('county', 64);
            $table->date('observed_at');
            $table->string('grade', 32)->default('standard');
            $table->decimal('price_per_kg_kes', 8, 2);
            $table->string('source', 32)->default('admin_csv');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['crop_id', 'market_name', 'observed_at', 'grade'], 'market_prices_unique');
            $table->index(['crop_id', 'observed_at']);
            $table->index(['crop_id', 'market_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};
