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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    /**
     * CSV export of cost entries for a season. Streamed so large seasons
     * don't buffer the full file in memory before send.
     */
    public function costsCsv(Season $season): StreamedResponse
    {
        $entries = $season->costEntries()->latest('incurred_at')->get();
        $filename = $this->csvFilename($season, 'costs');

        return response()->streamDownload(function () use ($entries) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['incurred_at', 'category', 'description', 'amount_kes', 'supplier_name']);
            foreach ($entries as $entry) {
                fputcsv($out, [
                    $entry->incurred_at?->toDateString(),
                    $entry->category,
                    $entry->description,
                    (string) $entry->amount_kes,
                    $entry->supplier_name,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function harvestsCsv(Season $season): StreamedResponse
    {
        $logs = $season->harvestLogs()->latest('harvested_at')->get();
        $filename = $this->csvFilename($season, 'harvests');

        return response()->streamDownload(function () use ($logs) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['harvested_at', 'quantity_kg', 'sold_quantity_kg', 'unit_price_kes', 'revenue_kes', 'buyer_name']);
            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->harvested_at?->toDateString(),
                    (string) $log->quantity_kg,
                    (string) $log->sold_quantity_kg,
                    $log->unit_price_kes !== null ? (string) $log->unit_price_kes : '',
                    number_format($log->revenueKes(), 2, '.', ''),
                    $log->buyer_name,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function activitiesCsv(Season $season): StreamedResponse
    {
        $activities = $season->activities()->orderBy('ideal_date')->get();
        $filename = $this->csvFilename($season, 'activities');

        return response()->streamDownload(function () use ($activities) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ideal_date', 'phase', 'activity_type', 'description_en', 'is_critical', 'status', 'completed_at']);
            foreach ($activities as $activity) {
                fputcsv($out, [
                    $activity->ideal_date?->toDateString(),
                    $activity->phase,
                    $activity->activity_type,
                    $activity->description_en,
                    $activity->is_critical ? 'yes' : 'no',
                    $activity->status,
                    $activity->completed_at?->toDateString(),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function csvFilename(Season $season, string $kind): string
    {
        $season->loadMissing('crop');

        return sprintf(
            'panda-season-%s-%s-%s.csv',
            $season->crop?->slug ?? 'crop',
            $kind,
            $season->id
        );
    }
}
