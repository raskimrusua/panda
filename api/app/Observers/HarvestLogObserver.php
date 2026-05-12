<?php

namespace App\Observers;

use App\Models\HarvestLog;
use App\Models\Season;
use Illuminate\Support\Facades\DB;

/**
 * On every HarvestLog change (create / update / delete), recompute the
 * Season's rolling cumulative harvest + revenue and stamp them onto
 * Season.engine_metadata. Keeps the season report endpoint fast — no need
 * to re-aggregate per request — and gives the frontend a single read for
 * "how am I doing".
 *
 * Recomputation is intentional (vs. incremental) because:
 *   1. Soft deletes complicate "subtract on delete";
 *   2. Updates can change quantity_kg AND sold_quantity_kg at the same time;
 *   3. The harvest_logs row count per season is small (10-30 typical, 50
 *      worst-case), so SUM() is sub-millisecond.
 */
class HarvestLogObserver
{
    public function created(HarvestLog $log): void
    {
        $this->recompute($log->season_id, $log->tenant_id);
    }

    public function updated(HarvestLog $log): void
    {
        $this->recompute($log->season_id, $log->tenant_id);
    }

    public function deleted(HarvestLog $log): void
    {
        $this->recompute($log->season_id, $log->tenant_id);
    }

    private function recompute(?string $seasonId, ?string $tenantId): void
    {
        if ($seasonId === null) {
            return;
        }

        $totals = DB::table('harvest_logs')
            ->where('season_id', $seasonId)
            ->whereNull('deleted_at')
            ->selectRaw('
                COALESCE(SUM(quantity_kg), 0) AS total_kg,
                COALESCE(SUM(sold_quantity_kg), 0) AS sold_kg,
                COALESCE(SUM(sold_quantity_kg * COALESCE(unit_price_kes, 0)), 0) AS revenue_kes,
                COUNT(*) AS log_count
            ')
            ->first();

        // Season is tenant-scoped; use withoutGlobalScopes so the observer
        // works in queue / artisan contexts where Tenant::current() is null.
        $season = Season::withoutGlobalScopes()
            ->where('id', $seasonId)
            ->first();
        if ($season === null) {
            return;
        }

        /** @var array<string, mixed> $existing */
        $existing = (array) ($season->engine_metadata ?? []);

        $season->update([
            'engine_metadata' => array_merge($existing, [
                'harvest_total_kg' => round((float) ($totals->total_kg ?? 0), 2),
                'harvest_sold_kg' => round((float) ($totals->sold_kg ?? 0), 2),
                'harvest_revenue_kes' => round((float) ($totals->revenue_kes ?? 0), 2),
                'harvest_log_count' => (int) ($totals->log_count ?? 0),
                'harvest_recomputed_at' => now()->toIso8601String(),
            ]),
        ]);
    }
}
