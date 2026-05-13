<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * DiseaseDetection — one row per disease scan.
 *
 * Tenant-scoped. Image stored via Storage facade (R2 in prod, local in
 * dev) — `image_url` is the public URL.
 *
 * `provider` distinguishes mock vs real Crop.health calls — important for
 * billing audit (only real `crop_health` calls hit the budget) and for
 * filtering during the P1-P4 mock period.
 *
 * `engine_response` is the raw provider payload. `top_diagnosis` +
 * `confidence` are the convenience fields the PWA renders without
 * re-parsing.
 *
 * `season_id` and `crop_id` are nullable — a farmer can scan a leaf on
 * a wild plant or on a season they haven't created yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disease_detections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('season_id')->nullable()->constrained('seasons')->nullOnDelete();
            $table->foreignUlid('crop_id')->nullable()->constrained('crops')->nullOnDelete();
            $table->string('image_url', 500);
            $table->string('provider', 32);
            $table->string('top_diagnosis', 200)->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->json('engine_response')->nullable();
            $table->json('treatments')->nullable();
            $table->boolean('opt_in_for_training')->default(false);
            $table->foreignUlid('captured_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'crop_id']);
            $table->index(['tenant_id', 'season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disease_detections');
    }
};
