<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per pending invitation. Token is a 64-char URL-safe string
 * generated server-side and emailed via TeamInvitationNotification.
 * accepted_at + accepted_by are filled in by /team/accept/{token}.
 * revoked_at lets the owner cancel a pending invite without deleting.
 *
 * Email uniqueness is enforced per (tenant_id, email) only while the
 * invite is pending — once accepted/revoked a fresh invite for the
 * same email is allowed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUlid('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email', 255);
            $table->string('name', 120)->nullable();
            $table->string('token', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->foreignUlid('accepted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};
