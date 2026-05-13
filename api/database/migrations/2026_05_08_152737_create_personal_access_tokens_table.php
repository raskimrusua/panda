<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sanctum's published migration uses `morphs('tokenable')` which creates a
 * bigint `tokenable_id`. Panda User PKs are ULIDs — inserting a ULID string
 * into a bigint column fails in Postgres (`SQLSTATE[22P02]: invalid input
 * syntax for type bigint`). SQLite is typeless and silently accepts the
 * mismatch — local tests pass, CI catches it. Switch to `ulidMorphs()`
 * (Laravel 11 native).
 *
 * Same lesson as activity_log subject_id (PR #2 fix).
 * See skill-laravel-eloquent-model.md v1.1 Edge Cases.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->ulidMorphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_access_tokens');
    }
};
