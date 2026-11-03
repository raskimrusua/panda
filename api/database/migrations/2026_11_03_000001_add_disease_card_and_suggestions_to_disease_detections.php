<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase A scanner enrichments on the scan row.
 *
 * `disease_card` — snapshot of the bilingual disease card (severity
 * classes, symptoms, treatment/prevention guidance, Swahili names) that
 * DiseaseCardResolver matched to the provider's diagnosis at scan time.
 * Snapshot (not live lookup) so history rows keep the content they were
 * shown, even as agronomist content evolves.
 *
 * `suggestions` — normalised top-3 alternative diagnoses
 * [{name, probability}] across providers (mock + Kindwise), so the PWA
 * does not parse provider-specific engine_response shapes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('disease_detections', function (Blueprint $table) {
            $table->json('disease_card')->nullable()->after('treatments');
            $table->json('suggestions')->nullable()->after('disease_card');
        });
    }

    public function down(): void
    {
        Schema::table('disease_detections', function (Blueprint $table) {
            $table->dropColumn(['disease_card', 'suggestions']);
        });
    }
};
