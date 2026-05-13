<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * InputListItem — one row per recommended input (seed, fertiliser, pesticide,
 * equipment, packaging) for a Season, scaled to the farmer's acreage.
 *
 * Engine reads `inputs_per_acre[]` from the crop JSON and writes one row each.
 * `quantity_scaled` = `quantity_per_acre * acreage * adjustment_multiplier`
 * (greenhouse cuts pesticides 40%, etc.).
 *
 * `procured_quantity` and `procured_at` are filled in P3 when the farmer logs
 * a purchase; left null on engine creation.
 *
 * `benchmark_price_kes` is the BASE per-acre indicative price from JAICA;
 * `cost_estimate_kes` is the scaled total. Both stored so we can compare
 * benchmark vs reality once CostEntry lands in P3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('input_list_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->string('input_type', 32);
            $table->string('product_name', 200);
            $table->decimal('quantity_per_acre', 12, 4);
            $table->decimal('quantity_scaled', 12, 4);
            $table->string('unit', 16);
            $table->smallInteger('week_from_planting');
            $table->decimal('benchmark_price_kes', 10, 2)->nullable();
            $table->decimal('cost_estimate_kes', 12, 2)->nullable();
            $table->boolean('pcpb_registered')->default(false);
            $table->json('alternatives')->nullable();
            $table->decimal('procured_quantity', 12, 4)->nullable();
            $table->timestamp('procured_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'season_id']);
            $table->index(['tenant_id', 'input_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('input_list_items');
    }
};
