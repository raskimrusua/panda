<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * user_consents — append-only audit trail satisfying Kenya DPA 2019 §30
 * burden of proof. One row per (user, policy_type, version). Frontend
 * UI never deletes rows; even superuser-side admin should not — keep
 * the trail intact for ODPC audits.
 *
 * Schema mirrors Shira's UserConsent model (apps/core/models/consent.py)
 * verbatim so cross-product audits compare cleanly. Index + unique
 * constraint names match Shira's `uniq_userconsent_user_policy_version`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('policy_type', 16);
            $table->string('version', 32);
            $table->timestamp('accepted_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->unique(
                ['user_id', 'policy_type', 'version'],
                'uniq_userconsent_user_policy_version'
            );
            $table->index(['user_id', 'policy_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};
