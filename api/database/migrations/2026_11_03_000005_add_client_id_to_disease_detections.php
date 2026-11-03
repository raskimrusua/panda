<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase B3 offline-sync idempotency key. Offline tree scans are queued
 * on-device and POSTed when connectivity returns; the queue retries on
 * flaky networks, so re-sends must never duplicate a row. Unique per
 * tenant — NULLs never collide in MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_detections', function (Blueprint $table) {
            $table->string('client_id', 40)->nullable()->after('captured_by');
            $table->unique(['tenant_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::table('disease_detections', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'client_id']);
            $table->dropColumn(['client_id']);
        });
    }
};
