<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase B3 — offline decision-tree rows have no photo. The column was
 * NOT NULL since P4 (every row went through the upload path); tree
 * results legitimately carry no image.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_detections', function (Blueprint $table) {
            $table->string('image_path', 500)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('disease_detections', function (Blueprint $table) {
            $table->string('image_path', 500)->nullable(false)->change();
        });
    }
};
