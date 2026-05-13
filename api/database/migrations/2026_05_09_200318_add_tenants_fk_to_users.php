<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the `users.tenant_id -> tenants.id` FK with cascade delete.
 *
 * Why a separate migration? The Laravel scaffold puts `create_users_table`
 * at timestamp `0001_01_01_000000`, which is the earliest sortable
 * timestamp Laravel uses. `create_tenants_table` lands later in the
 * timeline (2026_05_09_200317), so an inline `->constrained('tenants')`
 * on the users table would fail at migrate time — Postgres rejects forward
 * FK targets even when SQLite (in local dev) tolerates them.
 *
 * This migration runs after `create_tenants_table` and after the users
 * table exists with its `tenant_id` column, so the constraint can be
 * added cleanly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
    }
};
