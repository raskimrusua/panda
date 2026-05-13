<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Greenfield Panda — users get ULID PKs (matches every other Panda model)
 * and a `tenant_id` ULID column from day one. Single-DB row-based multitenancy
 * scopes every business model by tenant_id; users belong to exactly one tenant.
 *
 * `tenant_id` is nullable on users so the very first user (the farm owner)
 * can be created in the same atomic transaction as their tenant during
 * registration. The pair is committed together; mid-state never persists.
 *
 * The actual FK to `tenants` is added in a later migration
 * (2026_05_09_200318_add_tenants_fk_to_users) — Laravel uses timestamp
 * `0001_01_01_000000` for the user table, which sorts before the
 * `create_tenants_table` migration. SQLite tolerates a forward FK silently;
 * Postgres errors. Splitting the constraint into a post-tenants migration
 * keeps the column shape here and defers the FK until both tables exist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->index('tenant_id');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUlid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
