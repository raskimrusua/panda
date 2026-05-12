<?php

namespace App\Services\Crops\SeasonEngine;

use Carbon\CarbonImmutable;

/**
 * One planned activity in the engine output. Maps 1:1 to a SeasonActivity row
 * the observer will persist.
 */
final readonly class ActivityPlan
{
    public function __construct(
        public string $activityType,
        public string $phase,
        public CarbonImmutable $idealDate,
        public int $weekFromPlanting,
        public int $dayWindow,
        public string $descriptionEn,
        public string $descriptionSw,
        public ?string $tipEn,
        public ?string $tipSw,
        public bool $isCritical,
    ) {}
}
