<?php

namespace App\Services\Crops\SeasonEngine;

use Carbon\CarbonImmutable;

/**
 * Inputs the engine needs to plan a Season.
 *
 * Pure value object — no DB, no Eloquent. The observer constructs this from
 * a Season row before calling SeasonEngine::generate().
 */
final readonly class SeasonEngineInput
{
    public function __construct(
        public string $cropSlug,
        public string $tenantId,
        public string $seasonId,
        public float $acreage,
        public CarbonImmutable $plantingDate,
        public string $irrigationType,
        public ?string $county = null,
    ) {}
}
