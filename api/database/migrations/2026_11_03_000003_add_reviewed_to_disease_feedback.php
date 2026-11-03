<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase B feedback-consumption stamps. `reviewed_at` / `reviewed_by`
 * track which corrections ops has pulled into a training batch — the
 * retrain queue's triage signal. NULL = still in the queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_feedback', function (Blueprint $table) {
            $table->timestamp('reviewed_at')->nullable()->after('correction_text');
            $table->foreignUlid('reviewed_by')->nullable()->after('reviewed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('disease_feedback', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at']);
        });
    }
};
