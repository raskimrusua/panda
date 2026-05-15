<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `role` column to users for tenant-level team management.
 *
 * Two roles in this MVP:
 *   - owner: created the tenant via /auth/register; sees /team page;
 *     can invite + remove members.
 *   - member: was invited; everyday CRUD inside the tenant; can't manage
 *     the team.
 *
 * Backfill: for every tenant, the user with the earliest `created_at`
 * is set to 'owner'; all others to 'member'. Tenants that pre-date
 * this migration would normally have exactly one user (registration
 * created one). New users registered after this migration also default
 * to 'owner' (because they're creating their own tenant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 16)->default('owner')->after('is_superuser');
        });

        $rows = DB::table('users')
            ->select('id', 'tenant_id', 'created_at')
            ->whereNotNull('tenant_id')
            ->orderBy('tenant_id')
            ->orderBy('created_at')
            ->get();

        $seenTenant = [];
        foreach ($rows as $row) {
            if (! isset($seenTenant[$row->tenant_id])) {
                $seenTenant[$row->tenant_id] = true;

                continue;
            }
            DB::table('users')->where('id', $row->id)->update(['role' => 'member']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
