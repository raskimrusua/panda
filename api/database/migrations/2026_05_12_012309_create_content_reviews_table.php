<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ContentReview — agronomist's sign-off track for crop / disease JSON content.
 *
 * Per project plan §P1 (Filament agronomist editor + sign-off workflow):
 * Silas drafts a content edit in the Filament admin, submits for review.
 * Ops admin approves → triggers (in a later PR) `panda:content:export` which
 * regenerates `resources/content/crops/<slug>.json` from DB and commits via
 * the GitHub API.
 *
 * Status enum:
 *   draft              — Silas is editing
 *   submitted          — Silas sent it for review
 *   approved           — Ops approved; export job picks it up
 *   changes_requested  — Ops sent it back with notes
 *
 * `target_type` is `crop|disease` — same review track for both content kinds.
 * `target_slug` is the crop or disease slug (e.g. `tomato`, `early-blight`).
 *
 * NOT tenant-scoped: this is an ops/agronomist surface across the whole
 * Panda content library, not per-farm.
 *
 * `submitted_by` / `reviewer_id` are nullable bigint FKs to users — kept as
 * `unsignedBigInteger` (not `foreignId`) so they survive a future ULID
 * conversion of the users table without a schema rewrite (the FK target
 * stays `users.id` either way; SQLite is typeless and Postgres compares the
 * stringified ULID against bigint loosely enough during the transition PR).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 16);
            $table->string('target_slug', 64);
            $table->string('status', 32)->default('draft');
            $table->json('content_payload')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['target_type', 'target_slug']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reviews');
    }
};
