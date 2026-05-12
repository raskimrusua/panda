<?php

namespace App\Http\Controllers\Api\V1\Seasons;

use App\Http\Controllers\Controller;
use App\Http\Resources\CostEntryResource;
use App\Http\Resources\HarvestLogResource;
use App\Http\Resources\InputListItemResource;
use App\Http\Resources\SeasonActivityResource;
use App\Models\Season;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant-scoped — every fetch passes through the Season's global scope so
 * a foreign-tenant Season URL 404s before any nested data is returned.
 */
class SeasonNestedController extends Controller
{
    public function timeline(Season $season): AnonymousResourceCollection
    {
        return SeasonActivityResource::collection(
            $season->activities()->orderBy('ideal_date')->get()
        );
    }

    public function inputList(Season $season): AnonymousResourceCollection
    {
        return InputListItemResource::collection(
            $season->inputListItems()->orderBy('week_from_planting')->get()
        );
    }

    public function costs(Season $season): JsonResponse
    {
        $entries = $season->costEntries()->latest('incurred_at')->get();

        $byCategory = $entries->groupBy('category')->map(
            fn ($group) => round((float) $group->sum('amount_kes'), 2)
        );

        return response()->json([
            'data' => CostEntryResource::collection($entries),
            'totals' => [
                'by_category' => $byCategory,
                'all_kes' => round((float) $entries->sum('amount_kes'), 2),
            ],
        ]);
    }

    public function harvests(Season $season): JsonResponse
    {
        $logs = $season->harvestLogs()->latest('harvested_at')->get();

        $totalKg = (float) $logs->sum('quantity_kg');
        $soldKg = (float) $logs->sum('sold_quantity_kg');
        $revenue = $logs->reduce(fn (float $acc, $log) => $acc + $log->revenueKes(), 0.0);

        return response()->json([
            'data' => HarvestLogResource::collection($logs),
            'totals' => [
                'quantity_kg' => round($totalKg, 2),
                'sold_quantity_kg' => round($soldKg, 2),
                'revenue_kes' => round($revenue, 2),
            ],
        ]);
    }

    /**
     * PDF season report — bundles activity timeline + input list + costs +
     * harvests into one downloadable PDF for the farmer or a lender.
     */
    public function report(Season $season): Response
    {
        $season->load(['crop', 'tenant']);
        $activities = $season->activities()->orderBy('ideal_date')->get();
        $inputs = $season->inputListItems()->orderBy('week_from_planting')->get();
        $costs = $season->costEntries()->latest('incurred_at')->get();
        $harvests = $season->harvestLogs()->latest('harvested_at')->get();

        $totals = [
            'cost_total_kes' => round((float) $costs->sum('amount_kes'), 2),
            'harvest_total_kg' => round((float) $harvests->sum('quantity_kg'), 2),
            'sold_kg' => round((float) $harvests->sum('sold_quantity_kg'), 2),
            'revenue_kes' => $harvests->reduce(fn (float $acc, $log) => $acc + $log->revenueKes(), 0.0),
        ];
        $totals['profit_kes'] = round($totals['revenue_kes'] - $totals['cost_total_kes'], 2);

        $pdf = Pdf::loadView('pdf.season-report', [
            'season' => $season,
            'activities' => $activities,
            'inputs' => $inputs,
            'costs' => $costs,
            'harvests' => $harvests,
            'totals' => $totals,
        ]);

        $filename = sprintf(
            'panda-season-%s-%s.pdf',
            $season->crop?->slug ?? 'crop',
            $season->id
        );

        return $pdf->download($filename);
    }
}
