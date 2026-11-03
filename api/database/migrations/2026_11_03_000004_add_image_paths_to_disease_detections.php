<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase B multi-image upload. `image_paths` holds every photo from a
 * scan (first element = primary `image_path`). Kept separate from the
 * legacy `image_path` column so old clients/rows need no backfill.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_detections', function (Blueprint $table) {
            $table->json('image_paths')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('disease_detections', function (Blueprint $table) {
            $table->dropColumn(['image_paths']);
        });
    }
};
