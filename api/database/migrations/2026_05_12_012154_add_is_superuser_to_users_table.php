<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * is_superuser gates Filament panel access (User::canAccessPanel checks this).
 *
 * Default false — every farmer user gets it false. Ops/agronomists are flipped
 * manually (CLI: `php artisan tinker` or, post-Filament-bootstrap, via the
 * panel itself by an existing superuser).
 *
 * Separate migration (not edited into the original users-create) so it applies
 * cleanly on top of either the bigint-PK users table currently on main OR the
 * ULID-PK users table after PR #3 lands. Just adds a boolean — no FK, no PK
 * touch, no conflict.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_superuser')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_superuser');
        });
    }
};
