<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Farmer feedback on a scan result — Phase A capture half of the
 * Phase B correction feedback loop (retrain queue).
 *
 * One row per scan per user (unique index) — re-submission updates the
 * verdict in place. `correction_key` is an optional disease key from the
 * crop content layer when the farmer picks the right answer from a list;
 * `correction_text` is the free-text fallback ("it was actually mites").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disease_feedback', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('disease_detection_id')->constrained('disease_detections')->cascadeOnDelete();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verdict', 16);
            $table->string('correction_key', 100)->nullable();
            $table->string('correction_text', 500)->nullable();
            $table->timestamps();

            $table->unique(['disease_detection_id', 'user_id']);
            $table->index(['tenant_id', 'verdict']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_feedback');
    }
};
