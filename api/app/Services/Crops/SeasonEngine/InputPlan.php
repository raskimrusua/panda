<?php

namespace App\Services\Crops\SeasonEngine;

/**
 * One planned input in the engine output. Maps 1:1 to an InputListItem row
 * the observer will persist.
 *
 * Quantities are decimals (PHP float for in-flight; persisted as decimal:4).
 */
final readonly class InputPlan
{
    /**
     * @param  list<string>|null  $alternatives
     */
    public function __construct(
        public string $inputType,
        public string $productName,
        public float $quantityPerAcre,
        public float $quantityScaled,
        public string $unit,
        public int $weekFromPlanting,
        public ?float $benchmarkPriceKes,
        public ?float $costEstimateKes,
        public bool $pcpbRegistered,
        public ?array $alternatives,
    ) {}
}
