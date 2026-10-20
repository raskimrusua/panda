<?php

namespace App\Http\Controllers\Api\V1\Prices;

use App\Http\Controllers\Controller;
use App\Http\Resources\MarketPriceResource;
use App\Models\Crop;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

/**
 * Public market price endpoints. Crop is resolved by slug (the same as
 * the public Crop endpoints) — public catalogue, not tenant-scoped.
 *
 * Three surfaces:
 *   GET /api/v1/prices/{crop:slug}/latest    — latest price per market
 *   GET /api/v1/prices/{crop:slug}/history   — full historical series
 *   GET /api/v1/prices/{crop:slug}/forecast  — rule-based 3-mo forecast
 *
 * Forecast is a per-month average over the last 12 months by month-of-year,
 * projected forward 3 months. Cheap, transparent, good enough until P5+
 * brings real ARIMA / LSTM. Marked clearly so a farmer reads it as
 * "history-implied trend" not "guaranteed".
 */
class MarketPriceController extends Controller
{
    public function latest(Crop $crop): JsonResponse
    {
        // For each market_name, the most recent observation.
        $rows = MarketPrice::query()
            ->where('crop_id', $crop->id)
            ->orderByDesc('observed_at')
            ->get()
            ->groupBy('market_name')
            ->map(fn ($group) => $group->first())
            ->values();

        return response()->json([
            'data' => MarketPriceResource::collection($rows),
            'meta' => [
                'crop_slug' => $crop->slug,
                'market_count' => $rows->count(),
            ],
        ]);
    }

    public function history(Crop $crop): JsonResponse
    {
        $rows = MarketPrice::query()
            ->where('crop_id', $crop->id)
            ->orderBy('observed_at')
            ->get();

        return response()->json([
            'data' => MarketPriceResource::collection($rows),
            'meta' => [
                'crop_slug' => $crop->slug,
                'observation_count' => $rows->count(),
                'date_range' => [
                    'start' => $rows->first()?->observed_at?->toDateString(),
                    'end' => $rows->last()?->observed_at?->toDateString(),
                ],
            ],
        ]);
    }

    /**
     * Rule-based 3-month forward forecast: average price by month-of-year
     * across all observed history, projected onto the next 3 months.
     */
    public function forecast(Crop $crop): JsonResponse
    {
        $rows = MarketPrice::query()
            ->where('crop_id', $crop->id)
            ->get();

        if ($rows->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => ['method' => 'monthly_average', 'note' => 'No history available.'],
                'disclaimer' => (string) config('legal.price_disclaimer'),
            ]);
        }

        // Group by (calendar) month of observed_at, average the prices.
        $monthlyAverages = $rows
            ->groupBy(fn ($r) => (int) CarbonImmutable::parse((string) $r->observed_at)->format('n'))
            ->map(fn ($group) => round((float) $group->avg('price_per_kg_kes'), 2));

        $forecast = [];
        $now = now();
        for ($i = 1; $i <= 3; $i++) {
            $target = $now->copy()->addMonthsNoOverflow($i);
            $month = (int) $target->format('n');
            $forecast[] = [
                'month' => $target->format('Y-m'),
                'projected_price_per_kg_kes' => $monthlyAverages[$month] ?? null,
                'method' => 'historical_monthly_average',
            ];
        }

        return response()->json([
            'data' => $forecast,
            'meta' => [
                'crop_slug' => $crop->slug,
                'method' => 'monthly_average',
                'history_observations' => $rows->count(),
                'note' => 'Trend signal only; not a guarantee. Will be replaced by a learned model in a later release.',
            ],
            // Top-level disclaimer mirrors MarketPriceResource so every
            // pricing surface ships the same advisory text.
            'disclaimer' => (string) config('legal.price_disclaimer'),
        ]);
    }
}
