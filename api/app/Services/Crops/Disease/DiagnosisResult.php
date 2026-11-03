<?php

namespace App\Services\Crops\Disease;

/**
 * Result returned by any CropHealthClient implementation. Pure value
 * object — adapter doesn't write to DB.
 *
 * `treatments` is a list of generic-chemical → PCPB-registered-product
 * mappings. PcpbEnrichment (P5) replaces the generic with the Kenyan
 * registered product; for now, the mock returns a hand-curated list.
 */
final readonly class DiagnosisResult
{
    /**
     * @param  list<array{generic: string, pcpb: ?string, application_notes?: string}>  $treatments
     * @param  array<string, mixed>  $rawResponse
     */
    public function __construct(
        public string $provider,
        public string $topDiagnosis,
        public float $confidence,
        public array $treatments,
        public array $rawResponse,
    ) {}

    /**
     * Normalised top-N alternative diagnoses from the raw provider payload.
     * Understands both shapes: mock `suggestions[]` and Kindwise
     * `result.disease.suggestions[]` — so the PWA never parses provider
     * internals.
     *
     * @return list<array{name: string, probability: float}>
     */
    public function suggestions(int $limit = 3): array
    {
        $items = data_get($this->rawResponse, 'result.disease.suggestions');
        if (! is_array($items)) {
            $items = $this->rawResponse['suggestions'] ?? [];
        }

        $out = [];
        foreach ($items as $suggestion) {
            if (! is_array($suggestion)) {
                continue;
            }
            $name = $suggestion['name'] ?? null;
            if (! is_string($name) || $name === '') {
                continue;
            }
            $probability = $suggestion['probability'] ?? null;
            $out[] = [
                'name' => $name,
                'probability' => is_numeric($probability) ? round((float) $probability, 4) : 0.0,
            ];
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }
}
