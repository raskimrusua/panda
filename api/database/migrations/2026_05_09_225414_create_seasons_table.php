<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Season — first tenant-scoped business model in Panda.
 *
 * A Season is one farmer's plan to grow one Crop on N acres starting on a
 * given date. The Season Engine (P3+) generates the activity timeline and
 * input list from this row, so schema decisions here ripple into engine
 * input shape and report layouts.
 *
 * `client_id` is the offline-sync idempotency key (matches Shira PWA pattern):
 * the same client_id submitted twice yields the same Season, never duplicate.
 * Unique constraint scoped to tenant_id (skill-laravel-eloquent-model rule).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('crop_id')->constrained('crops')->restrictOnDelete();
            $table->decimal('acreage', 8, 2);
            $table->date('planting_date');
            $table->string('status', 32)->default('planning');
            $table->string('irrigation_type', 32)->default('rainfed');
            $table->json('engine_metadata')->nullable();
            $table->ulid('client_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'planting_date']);
            $table->unique(['tenant_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
