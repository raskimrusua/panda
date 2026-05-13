<?php

namespace App\Services\Crops\SeasonEngine;

/**
 * What the engine returns. Pure value object — observer persists; engine
 * never writes to the DB.
 */
final readonly class SeasonEngineOutput
{
    /**
     * @param  list<ActivityPlan>  $activities
     * @param  list<InputPlan>  $inputs
     * @param  list<string>  $adjustmentsApplied  e.g. 'greenhouse_reduces_pesticides_by_40pct'
     */
    public function __construct(
        public array $activities,
        public array $inputs,
        public array $adjustmentsApplied,
        public float $costEstimateTotalKes,
    ) {}
}
