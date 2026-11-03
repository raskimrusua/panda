<?php

namespace App\Services\Crops\Disease;

use App\Models\DiseaseDetection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Real provider — Kindwise Crop.health API.
 *
 *   API:   POST https://crop.kindwise.com/api/v1/identification
 *   Auth:  Api-Key header
 *   Body:  { images: [base64...] } — minimal. Verified against model
 *          crop_health:1.2.1 (2026-10-27): the ONLY accepted modifier is
 *          `similar_images=true`. `classification_level`, `crop`, and
 *          `details` are rejected with HTTP 400 ("Unknown modifier").
 *
 * Single-image response shape (verified live):
 *   result.is_plant.probability
 *   result.disease.suggestions[].name + .probability + .details.treatment
 *
 * Multi-image (Phase B): the payload is an array by design, so a scan
 * sends all photos in one call. The response mapping is defensive:
 * legacy single `result.disease.suggestions` is used when present,
 * otherwise every `result[]` element's `disease.suggestions` are merged
 * and ranked by probability. Confirm the multi-shape against a live
 * call after first multi-image scan (the docs SPA does not publish the
 * schema).
 *
 * We map the top suggestion to topDiagnosis + confidence. If no
 * suggestions exist we fall back to the healthy/unknown path.
 *
 * NOTE: this API version does not return treatment data (the `details`
 * modifier that would include it is rejected), so treatments come from
 * the local PCPB `TreatmentCatalogue`.
 *
 * Costs: each call hits Kindwise's per-month identification quota.
 * `DiseaseDetection.provider = 'crop_health'` (vs 'mock') is the audit
 * field used to bill back / cap usage. If the API call fails we throw —
 * the controller decides whether to fall back to the mock or surface
 * the error to the user.
 */
class KindwiseCropHealthClient implements CropHealthClient
{
    public function diagnose(array $imagePaths, ?string $cropSlug = null): DiagnosisResult
    {
        $apiKey = (string) config('services.kindwise.key');
        $baseUrl = rtrim((string) config('services.kindwise.url'), '/');
        $timeout = (int) config('services.kindwise.timeout', 20);

        if ($apiKey === '') {
            throw new RuntimeException('Kindwise API key is not configured (KINDWISE_API_KEY).');
        }

        $images = [];
        foreach ($imagePaths as $imagePath) {
            try {
                $bytes = Storage::disk(config('filesystems.default'))->get($imagePath);
            } catch (\Throwable $e) {
                throw new RuntimeException("Could not read image at path: {$imagePath}", 0, $e);
            }
            if (! is_string($bytes) || $bytes === '') {
                throw new RuntimeException("Could not read image at path: {$imagePath}");
            }
            $images[] = base64_encode($bytes);
        }

        $payload = [
            'images' => $images,
        ];

        // The crop hint (and every other modifier except
        // `similar_images=true`) is rejected by the live API, so the slug
        // is not sent. $cropSlug is kept in the interface signature for
        // local treatment-catalogue narrowing + future API versions.

        $response = Http::withHeaders([
            'Api-Key' => $apiKey,
            'Content-Type' => 'application/json',
        ])
            ->timeout($timeout)
            ->post("{$baseUrl}/identification", $payload);

        if (! $response->successful()) {
            Log::warning('Kindwise Crop.health call failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'image_paths' => $imagePaths,
            ]);
            throw new RuntimeException('Crop.health API call failed (status '.$response->status().').');
        }

        return $this->mapResponse($response->json(), $cropSlug, $imagePaths);
    }

    /**
     * @param  array<mixed>  $raw
     * @param  list<string>  $imagePaths
     */
    private function mapResponse(array $raw, ?string $cropSlug = null, array $imagePaths = []): DiagnosisResult
    {
        $suggestions = $this->collectSuggestions($raw);
        $top = count($suggestions) > 0 ? $suggestions[0] : null;

        if ($top === null) {
            // Plant looks healthy or unidentifiable.
            return new DiagnosisResult(
                provider: DiseaseDetection::PROVIDER_CROP_HEALTH,
                topDiagnosis: 'No disease detected',
                confidence: 0.50,
                treatments: [],
                rawResponse: $raw,
            );
        }

        $treatments = [];
        $treatment = data_get($top, 'details.treatment');
        if (is_array($treatment)) {
            foreach ($treatment as $kind => $items) {
                if (! is_array($items)) {
                    continue;
                }
                foreach ($items as $item) {
                    $treatments[] = [
                        'generic' => is_string($item) ? $item : (string) ($item['name'] ?? ''),
                        'pcpb' => null,
                        'application_notes' => is_string($kind) ? ucfirst($kind) : null,
                    ];
                }
            }
        }

        // The live API (crop_health:1.2.1) rejects the `details` modifier,
        // so `details.treatment` is never present. Fall back to the local
        // PCPB catalogue so farmers still get actionable guidance.
        if ($treatments === []) {
            $treatments = TreatmentCatalogue::suggest(
                (string) data_get($top, 'name', ''),
                $cropSlug,
            );
        }

        // Normalised key so DiagnosisResult::suggestions() stays provider-shape-agnostic.
        $raw['suggestions'] = $suggestions;
        $raw['image_paths'] = $imagePaths;

        return new DiagnosisResult(
            provider: DiseaseDetection::PROVIDER_CROP_HEALTH,
            topDiagnosis: (string) data_get($top, 'name', 'Unknown'),
            confidence: (float) data_get($top, 'probability', 0.0),
            treatments: $treatments,
            rawResponse: $raw,
        );
    }

    /**
     * Merge disease suggestions from both response shapes and rank by
     * probability. Legacy single-image: `result.disease.suggestions`;
     * multi-image: `result[]` list of per-image result objects.
     *
     * @param  array<mixed>  $raw
     * @return list<array{name: mixed, probability: mixed}>
     */
    private function collectSuggestions(array $raw): array
    {
        $out = [];

        $legacy = data_get($raw, 'result.disease.suggestions');
        if (is_array($legacy)) {
            $out = array_merge($out, $legacy);
        }

        $results = data_get($raw, 'result');
        if (is_array($results) && array_is_list($results)) {
            foreach ($results as $result) {
                if (! is_array($result)) {
                    continue;
                }
                $perImage = data_get($result, 'disease.suggestions');
                if (is_array($perImage)) {
                    $out = array_merge($out, $perImage);
                }
            }
        }

        usort($out, function (array $a, array $b): int {
            return ((float) ($b['probability'] ?? 0.0)) <=> ((float) ($a['probability'] ?? 0.0));
        });

        return $out;
    }
}
