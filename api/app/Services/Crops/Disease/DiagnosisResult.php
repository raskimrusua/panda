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
}
