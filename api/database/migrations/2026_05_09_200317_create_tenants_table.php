<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant = Farm in Panda's domain (single-DB row-based multitenancy).
 *
 * Spatie's published migration assumed domain-per-tenant + database-per-tenant.
 * Panda uses single-DB row isolation: tenants are farms, scoped by tenant_id
 * column on every business model. No subdomain routing, no separate DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Identity
            $table->string('name', 200);                  // Farm name (farmer-chosen)
            $table->string('slug', 64)->unique();          // URL-safe shortname (auto-derived)

            // Kenya-context location (per JAICA spec — county is the primary unit)
            $table->string('county', 64);
            $table->string('sub_county', 64)->nullable();
            $table->string('ward', 64)->nullable();

            // Optional GPS for nearest-dealer search later
            $table->decimal('gps_lat', 9, 6)->nullable();
            $table->decimal('gps_lng', 9, 6)->nullable();

            // Per-tenant config (notification prefs, language default, etc.)
            $table->json('settings')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('county');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
