<?php

namespace App\Observers;

use App\Models\InputListItem;
use App\Models\Season;
use App\Models\SeasonActivity;
use App\Services\Crops\SeasonEngine\ActivityPlan;
use App\Services\Crops\SeasonEngine\InputPlan;
use App\Services\Crops\SeasonEngine\SeasonEngine;
use App\Services\Crops\SeasonEngine\SeasonEngineOutput;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Season::created → run engine → persist activities + inputs in one tx.
 *
 * If the engine throws (typically: no crop content for that slug), we LOG
 * and SWALLOW. The Season itself stays — a half-built season with notes
 * "engine couldn't generate" is recoverable; cancelling the create() is not
 * (the controller already returned 201 to the client and we don't get a
 * second chance to roll back the HTTP transaction).
 *
 * The engine_metadata column on Season is updated with the adjustments
 * list and the cost estimate so the GET /seasons/{id} endpoint can show
 * "we cut your pesticide budget 40% because greenhouse" without re-running.
 */
class SeasonObserver
{
    public function __construct(private readonly SeasonEngine $engine) {}

    public function created(Season $season): void
    {
        try {
            $output = $this->engine->generate(SeasonEngine::inputFromSeason($season));
        } catch (Throwable $e) {
            Log::error('SeasonObserver: engine failed; Season kept without timeline.', [
                'season_id' => $season->id,
                'crop_id' => $season->crop_id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        DB::transaction(function () use ($season, $output): void {
            $this->persistActivities($season, $output);
            $this->persistInputs($season, $output);
            $this->stampSeasonMetadata($season, $output);
        });
    }

    private function persistActivities(Season $season, SeasonEngineOutput $output): void
    {
        foreach ($output->activities as $a) {
            /** @var ActivityPlan $a */
            SeasonActivity::create([
                'tenant_id' => $season->tenant_id,
                'season_id' => $season->id,
                'activity_type' => $a->activityType,
                'phase' => $a->phase,
                'ideal_date' => $a->idealDate->toDateString(),
                'week_from_planting' => $a->weekFromPlanting,
                'day_window' => $a->dayWindow,
                'description_en' => $a->descriptionEn,
                'description_sw' => $a->descriptionSw,
                'tip_en' => $a->tipEn,
                'tip_sw' => $a->tipSw,
                'is_critical' => $a->isCritical,
                'status' => SeasonActivity::STATUS_PENDING,
            ]);
        }
    }

    private function persistInputs(Season $season, SeasonEngineOutput $output): void
    {
        foreach ($output->inputs as $i) {
            /** @var InputPlan $i */
            InputListItem::create([
                'tenant_id' => $season->tenant_id,
                'season_id' => $season->id,
                'input_type' => $i->inputType,
                'product_name' => $i->productName,
                'quantity_per_acre' => $i->quantityPerAcre,
                'quantity_scaled' => $i->quantityScaled,
                'unit' => $i->unit,
                'week_from_planting' => $i->weekFromPlanting,
                'benchmark_price_kes' => $i->benchmarkPriceKes,
                'cost_estimate_kes' => $i->costEstimateKes,
                'pcpb_registered' => $i->pcpbRegistered,
                'alternatives' => $i->alternatives,
            ]);
        }
    }

    private function stampSeasonMetadata(Season $season, SeasonEngineOutput $output): void
    {
        /** @var array<string, mixed> $existing */
        $existing = (array) ($season->engine_metadata ?? []);

        $season->update([
            'engine_metadata' => array_merge($existing, [
                'engine_run_at' => now()->toIso8601String(),
                'adjustments_applied' => $output->adjustmentsApplied,
                'cost_estimate_total_kes' => $output->costEstimateTotalKes,
                'activities_generated' => count($output->activities),
                'inputs_generated' => count($output->inputs),
            ]),
        ]);
    }
}
