<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CostEntry — what the farmer actually spent on a Season.
 *
 * Versus engine projections (`InputListItem.cost_estimate_kes`):
 *   InputListItem = "what the JAICA benchmark says it should cost"
 *   CostEntry     = "what I actually paid the dealer"
 * Both stored so the season report can show benchmark vs reality.
 *
 * `input_list_item_id` is nullable — if the farmer's purchase matches an
 * engine-recommended row (e.g. they bought the recommended Tylka F1 seed),
 * we link them. If they bought something not on the list (a tank for water
 * storage, a casual labour day), the FK stays null.
 *
 * `category` mirrors InputListItem.input_type vocabulary plus a 'labour'
 * bucket and an 'other' catch-all for things that aren't physical inputs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignUlid('input_list_item_id')->nullable()->constrained('input_list_items')->nullOnDelete();
            $table->string('category', 32);
            $table->string('description', 200);
            $table->decimal('amount_kes', 12, 2);
            $table->date('incurred_at');
            $table->string('supplier_name', 120)->nullable();
            $table->string('receipt_url', 500)->nullable();
            $table->foreignUlid('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'season_id']);
            $table->index(['tenant_id', 'incurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_entries');
    }
};
