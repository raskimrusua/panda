<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SeasonActivity — one row per activity in the engine-generated timeline.
 *
 * The Season Engine (P2) reads `resources/content/crops/<slug>.json` →
 * `timeline_template[]` and writes one row per template entry, with the
 * ideal_date computed as planting_date + (week_from_planting * 7).
 *
 * Status lifecycle:
 *   pending  — engine created it, farmer hasn't touched
 *   done     — farmer logged completion (P3 log-done route)
 *   skipped  — farmer marked irrelevant (e.g. greenhouse skips outdoor staking)
 *   overdue  — daily Celery sweep flips this when ideal_date is past + status=pending
 *
 * Indexed by [tenant_id, season_id] for the timeline-fetch hot path and by
 * [tenant_id, status] for "what's overdue across all my seasons" queries.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('season_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->string('activity_type', 64);
            $table->string('phase', 32);
            $table->date('ideal_date');
            $table->smallInteger('week_from_planting');
            $table->smallInteger('day_window')->default(0);
            $table->text('description_en');
            $table->text('description_sw');
            $table->text('tip_en')->nullable();
            $table->text('tip_sw')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->string('status', 16)->default('pending');
            $table->timestamp('completed_at')->nullable();
            $table->foreignUlid('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('completion_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'season_id']);
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'ideal_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('season_activities');
    }
};
