<?php

namespace App\Services\Crops\Disease;

/**
 * Local PCPB treatment catalogue.
 *
 * Kindwise's live identification API (model crop_health:1.2.1) rejects the
 * `details` modifier, so treatment data never arrives in the API response.
 * Treatments are therefore sourced locally: diagnosis name → curated
 * generic + PCPB-registered products. The mock provider draws from the
 * same catalogue so demo and live behaviour agree.
 */
final class TreatmentCatalogue
{
    /**
     * @var list<array{name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
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

    /**
     * @var list<array{name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
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

    /**
     * Full catalogue: tomato-specific entries (when a tomato crop hint is
     * given) followed by the generic entries.
     *
     * @return list<array{name: string, treatments: list<array{generic: string, pcpb: ?string, application_notes?: string}>}>
     */
    public static function catalogue(?string $cropSlug = null): array
    {
        return $cropSlug === 'tomato'
            ? array_merge(self::TOMATO_DISEASES, self::GENERIC_DISEASES)
            : self::GENERIC_DISEASES;
    }

    /**
     * Treatments for a diagnosis name (case-insensitive), or an empty list
     * when the diagnosis is not in the catalogue.
     *
     * @return list<array{generic: string, pcpb: ?string, application_notes?: string}>
     */
    public static function suggest(string $diagnosis, ?string $cropSlug = null): array
    {
        $needle = mb_strtolower(trim($diagnosis));

        foreach (self::catalogue($cropSlug) as $entry) {
            if (mb_strtolower($entry['name']) === $needle) {
                return $entry['treatments'];
            }
        }

        return [];
    }
}
