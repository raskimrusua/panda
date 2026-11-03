<?php

namespace App\Services\Crops\Disease;

/**
 * Contract every disease-detection provider implements.
 *
 * Real Kindwise Crop.health adapter lives in `KindwiseCropHealthClient`;
 * `MockCropHealthClient` is deterministic — no network, no billing.
 *
 * `imagePaths` is a Storage-facade path list (already saved by the
 * controller) — one path per photo, first = primary. Multi-image support
 * (Phase B) sends all of them in a single provider call; `cropSlug`
 * (when known) lets the provider narrow the candidate diseases locally.
 */
interface CropHealthClient
{
    /**
     * @param  list<string>  $imagePaths
     */
    public function diagnose(array $imagePaths, ?string $cropSlug = null): DiagnosisResult;
}
