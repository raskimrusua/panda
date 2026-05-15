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
 *   Body:  { images: [base64...], similar_images: true, classification_level: "species" }
 *
 * The response shape (relevant fields):
 *   result.classification.suggestions[].name
 *   result.classification.suggestions[].probability
 *   result.disease.suggestions[].name + .probability + .details.treatment
 *
 * We map the first disease suggestion to topDiagnosis + confidence. If
 * `disease.suggestions` is empty we fall back to `is_plant` / health
 * assessment messaging by leaving topDiagnosis null and returning a
 * low-confidence "healthy" result.
 *
 * Costs: each call hits Kindwise's per-month identification quota.
 * `DiseaseDetection.provider = 'crop_health'` (vs 'mock') is the audit
 * field used to bill back / cap usage. If the API call fails we throw —
 * the controller decides whether to fall back to the mock or surface
 * the error to the user.
 */
class KindwiseCropHealthClient implements CropHealthClient
{
    public function diagnose(string $imagePath, ?string $cropSlug = null): DiagnosisResult
    {
        $apiKey = (string) config('services.kindwise.key');
        $baseUrl = rtrim((string) config('services.kindwise.url'), '/');
        $timeout = (int) config('services.kindwise.timeout', 20);

        if ($apiKey === '') {
            throw new RuntimeException('Kindwise API key is not configured (KINDWISE_API_KEY).');
        }

        // Storage path -> raw bytes. The default disk is whatever
        // STORAGE_BACKEND resolves to (r2 in prod, local in dev) — same
        // facade ImageUploadService writes through, so private R2 photos
        // are read here without exposing a public URL.
        try {
            $bytes = Storage::disk(config('filesystems.default'))->get($imagePath);
        } catch (\Throwable $e) {
            throw new RuntimeException("Could not read image at path: {$imagePath}", 0, $e);
        }

        if (! is_string($bytes) || $bytes === '') {
            throw new RuntimeException("Could not read image at path: {$imagePath}");
        }

        $payload = [
            'images' => [base64_encode($bytes)],
            'similar_images' => false,
            'classification_level' => 'species',
        ];

        // Crop hint narrows Kindwise's candidate set. Slug→common name
        // mapping is intentionally minimal here; full localisation lives
        // elsewhere (see resources/content/crops/*.json).
        if ($cropSlug !== null) {
            $payload['crop'] = $cropSlug;
        }

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
                'image_path' => $imagePath,
            ]);
            throw new RuntimeException('Crop.health API call failed (status '.$response->status().').');
        }

        return $this->mapResponse($response->json());
    }

    /**
     * @param  array<mixed>  $raw
     */
    private function mapResponse(array $raw): DiagnosisResult
    {
        $diseaseSuggestions = data_get($raw, 'result.disease.suggestions', []);
        $top = is_array($diseaseSuggestions) && count($diseaseSuggestions) > 0
            ? $diseaseSuggestions[0]
            : null;

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

        return new DiagnosisResult(
            provider: DiseaseDetection::PROVIDER_CROP_HEALTH,
            topDiagnosis: (string) data_get($top, 'name', 'Unknown'),
            confidence: (float) data_get($top, 'probability', 0.0),
            treatments: $treatments,
            rawResponse: $raw,
        );
    }
}
