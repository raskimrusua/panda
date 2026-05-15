<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename `disease_detections.image_url` -> `image_path`.
 *
 * The column now stores the relative storage path (e.g. tenants/{id}/
 * disease/{id}.jpg) instead of a fully-qualified URL. The Resource
 * generates a signed time-limited URL on read via ImageUploadService,
 * which means farmer photos do not leak via stable public URLs.
 *
 * Legacy rows that already contain a full http(s) URL are passed through
 * untouched by `ImageUploadService::temporaryUrl()` — no data fix-up
 * needed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_detections', function (Blueprint $table) {
            $table->renameColumn('image_url', 'image_path');
        });
    }

    public function down(): void
    {
        Schema::table('disease_detections', function (Blueprint $table) {
            $table->renameColumn('image_path', 'image_url');
        });
    }
};
