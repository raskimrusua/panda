<?php

namespace App\Services\Crops\Disease;

use App\Models\DiseaseDetection;

/**
 * Deterministic mock for the Crop.health adapter.
 *
 * Used through the entire P1-P4 mock period. The full pipeline (image
 * upload → adapter call → PCPB enrichment → DiseaseDetection persistence)
 * is exercised by tests and the PWA so the cutover to the real Kindwise
 * adapter (P5) is a single binding swap in a service provider — no caller
 * change.
 *
 * Diagnosis is deterministic: hash(imagePath + cropSlug) → index into a
 * fixed catalogue. Same image always returns the same diagnosis, which
 * makes tests reproducible and lets the demo show repeatable behaviour.
 */
class MockCropHealthClient implements CropHealthClient
{
    /** @var list<array{name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}> */
    private const TOMATO_DISEASES = [
        [
            'name' => 'Early Blight',
            'treatments' => [
                ['generic' => 'Mancozeb 80WP', 'pcpb' => 'Ridomil Gold MZ 68 WG', 'application_notes' => 'Spray every 7-10 days at first sign.'],
                ['generic' => 'Copper Hydroxide', 'pcpb' => 'Funguran-OH 50 WP', 'application_notes' => 'Preventive — alternate with Ridomil to avoid resistance.'],
            ],
        ],
        [
            'name' => 'Late Blight',
            'treatments' => [
                ['generic' => 'Metalaxyl', 'pcpb' => 'Ridomil Gold MZ 68 WG', 'application_notes' => 'Apply within 24h of detection. Critical in cool wet weather.'],
            ],
        ],
        [
            'name' => 'Tomato Yellow Leaf Curl Virus (TYLCV)',
            'treatments' => [
                ['generic' => 'Imidacloprid', 'pcpb' => 'Confidor 200 SL', 'application_notes' => 'Targets the whitefly vector — not the virus itself. Plant TYLCV-resistant varieties (Tylka F1).'],
            ],
        ],
    ];

    /** @var list<array{name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}> */
    private const GENERIC_DISEASES = [
        [
            'name' => 'Powdery Mildew',
            'treatments' => [
                ['generic' => 'Sulphur', 'pcpb' => 'Thiovit Jet 80 WG', 'application_notes' => 'Apply in cool morning hours. Avoid temps above 30°C.'],
            ],
        ],
        [
            'name' => 'Aphid Infestation',
            'treatments' => [
                ['generic' => 'Pyrethrin', 'pcpb' => 'Ambush Super 200 EC', 'application_notes' => 'Selective — preserves beneficial insects.'],
            ],
        ],
    ];

    public function diagnose(string $imagePath, ?string $cropSlug = null): DiagnosisResult
    {
        $catalogue = $cropSlug === 'tomato'
            ? array_merge(self::TOMATO_DISEASES, self::GENERIC_DISEASES)
            : self::GENERIC_DISEASES;

        $idx = (int) (hexdec(substr(md5($imagePath.($cropSlug ?? '')), 0, 8)) % count($catalogue));
        $disease = $catalogue[$idx];

        // Confidence band: deterministic but plausible (0.72-0.96).
        $confidence = 0.72 + (hexdec(substr(md5($imagePath), 8, 4)) % 240) / 1000;
        $confidence = round($confidence, 4);

        $raw = [
            'provider' => DiseaseDetection::PROVIDER_MOCK,
            'suggestions' => array_map(
                fn (array $d) => ['name' => $d['name'], 'probability' => round(0.05 + mt_rand(0, 40) / 100, 4)],
                array_slice($catalogue, 0, 3)
            ),
            'image_path' => $imagePath,
            'crop_slug' => $cropSlug,
        ];
        // Force the top suggestion to match our deterministic pick.
        $raw['suggestions'][0]['name'] = $disease['name'];
        $raw['suggestions'][0]['probability'] = $confidence;

        return new DiagnosisResult(
            provider: DiseaseDetection::PROVIDER_MOCK,
            topDiagnosis: $disease['name'],
            confidence: $confidence,
            treatments: $disease['treatments'],
            rawResponse: $raw,
        );
    }
}
