<?php

namespace App\Services\Crops\Disease;

/**
 * Contract every disease-detection provider implements.
 *
 * Real Kindwise Crop.health adapter lands in P5 (`KindwiseCropHealthClient`).
 * MockCropHealthClient is used through P4 — deterministic, no network,
 * no billing.
 *
 * `imagePath` is a Storage facade path (already saved by the controller).
 * `cropSlug` (when known) lets the provider narrow the candidate diseases
 * — a tomato leaf scan never returns "potato late blight" as the top
 * diagnosis.
 */
interface CropHealthClient
{
    public function diagnose(string $imagePath, ?string $cropSlug = null): DiagnosisResult;
}
