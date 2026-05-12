<?php

namespace App\Services\Crops\SeasonEngine;

use App\Models\Season;
use App\Services\Content\ContentLoader;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Generates the activity timeline + scaled input list for a Season.
 *
 * - PURE class: no DB writes, no Eloquent. Output is a value object the
 *   observer persists atomically.
 * - Reads timeline_template + inputs_per_acre from the JSON content cached
 *   in Redis via ContentLoader.
 * - Adjustments (so far):
 *     greenhouse → pesticide quantities × 0.6 (JAICA: 40% reduction)
 *     drip       → captured as "applied" tag (water-saving signal; no
 *                  quantity changes here — used downstream by reporting)
 *     rainfed    → captured as a risk flag in adjustments_applied
 *     county altitude check → flag only if outside crop's ecological range
 *
 * Per-irrigation behaviour deliberately conservative for v1: we only adjust
 * what JAICA explicitly documents (pesticide × greenhouse). Yield bumps for
 * greenhouse / drip belong in the cost projection layer (P3), not in the
 * input list.
 */
class SeasonEngine
{
    public const IRRIGATION_RAINFED = 'rainfed';

    public const IRRIGATION_DRIP = 'drip';

    public const IRRIGATION_FURROW = 'furrow';

    public const IRRIGATION_GREENHOUSE = 'greenhouse';

    public const ADJ_GREENHOUSE_PESTICIDE_CUT = 'greenhouse_reduces_pesticides_by_40pct';

    public const ADJ_DRIP_WATER_SAVING = 'drip_irrigation_applied';

    public const ADJ_RAINFED_RISK = 'rainfed_dry_season_risk_flag';

    public const ADJ_ALTITUDE_OUT_OF_RANGE = 'county_altitude_outside_crop_range';

    private const PESTICIDE_INPUT_TYPE = 'chemical';

    private const GREENHOUSE_PESTICIDE_MULTIPLIER = 0.6;

    public function __construct(private readonly ContentLoader $loader) {}

    /**
     * Throws InvalidArgumentException if the crop slug isn't in the content
     * library — caller (the observer) should catch and log; we never want a
     * Season::created handler to crash and leave a half-built Season.
     */
    public function generate(SeasonEngineInput $input): SeasonEngineOutput
    {
        $content = $this->loader->getCrop($input->cropSlug);

        if ($content === null) {
            throw new InvalidArgumentException("No crop content for slug '{$input->cropSlug}'.");
        }

        $adjustments = $this->collectAdjustments($input, $content);
        $activities = $this->buildActivities($input, $content);
        $inputs = $this->buildInputs($input, $content, $adjustments);
        $totalCost = $this->sumCost($inputs);

        return new SeasonEngineOutput(
            activities: $activities,
            inputs: $inputs,
            adjustmentsApplied: $adjustments,
            costEstimateTotalKes: $totalCost,
        );
    }

    /**
     * @param  array<string, mixed>  $content
     * @return list<ActivityPlan>
     */
    private function buildActivities(SeasonEngineInput $input, array $content): array
    {
        $template = $content['timeline_template'] ?? [];
        if (! is_array($template)) {
            return [];
        }

        $out = [];
        foreach ($template as $row) {
            $week = (int) ($row['week_from_planting'] ?? 0);
            $out[] = new ActivityPlan(
                activityType: (string) ($row['activity_type'] ?? 'unknown'),
                phase: (string) ($row['phase'] ?? 'unknown'),
                idealDate: $input->plantingDate->addWeeks($week),
                weekFromPlanting: $week,
                dayWindow: (int) ($row['day_window'] ?? 0),
                descriptionEn: (string) ($row['description_en'] ?? ''),
                descriptionSw: (string) ($row['description_sw'] ?? ''),
                tipEn: isset($row['tip_en']) ? (string) $row['tip_en'] : null,
                tipSw: isset($row['tip_sw']) ? (string) $row['tip_sw'] : null,
                isCritical: (bool) ($row['is_critical'] ?? false),
            );
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $content
     * @param  list<string>  $adjustmentsApplied
     * @return list<InputPlan>
     */
    private function buildInputs(SeasonEngineInput $input, array $content, array $adjustmentsApplied): array
    {
        $rows = $content['inputs_per_acre'] ?? [];
        if (! is_array($rows)) {
            return [];
        }

        $greenhouseCut = in_array(self::ADJ_GREENHOUSE_PESTICIDE_CUT, $adjustmentsApplied, true);

        $out = [];
        foreach ($rows as $row) {
            $type = (string) ($row['input_type'] ?? InputPlan::class);
            $perAcre = (float) ($row['quantity'] ?? 0);

            $multiplier = ($greenhouseCut && $type === self::PESTICIDE_INPUT_TYPE)
                ? self::GREENHOUSE_PESTICIDE_MULTIPLIER
                : 1.0;

            $scaled = $perAcre * $input->acreage * $multiplier;
            $benchmark = isset($row['benchmark_price_kes']) ? (float) $row['benchmark_price_kes'] : null;
            // Benchmark is the single-acre KES price; cost estimate scales by
            // acreage AND the same pesticide multiplier so ghouse stays cheaper.
            $cost = $benchmark !== null ? round($benchmark * $input->acreage * $multiplier, 2) : null;

            /** @var list<string>|null $alts */
            $alts = isset($row['alternatives']) && is_array($row['alternatives'])
                ? array_values(array_map('strval', $row['alternatives']))
                : null;

            $out[] = new InputPlan(
                inputType: $type,
                productName: (string) ($row['product_name'] ?? ''),
                quantityPerAcre: $perAcre,
                quantityScaled: round($scaled, 4),
                unit: (string) ($row['unit'] ?? ''),
                weekFromPlanting: (int) ($row['week_from_planting'] ?? 0),
                benchmarkPriceKes: $benchmark,
                costEstimateKes: $cost,
                pcpbRegistered: (bool) ($row['pcpb_registered'] ?? false),
                alternatives: $alts,
            );
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return list<string>
     */
    private function collectAdjustments(SeasonEngineInput $input, array $content): array
    {
        $applied = [];

        if ($input->irrigationType === self::IRRIGATION_GREENHOUSE) {
            $applied[] = self::ADJ_GREENHOUSE_PESTICIDE_CUT;
        }
        if ($input->irrigationType === self::IRRIGATION_DRIP) {
            $applied[] = self::ADJ_DRIP_WATER_SAVING;
        }
        if ($input->irrigationType === self::IRRIGATION_RAINFED) {
            $applied[] = self::ADJ_RAINFED_RISK;
        }

        // County-altitude check left as a flag-only hook for now. A real
        // county→altitude lookup table lands when Dealer + dealer GPS work
        // in P4 — that'll seed the same data we'd need here. For v1 we just
        // surface the crop's published ecological range.
        $eco = $content['ecological_requirements'] ?? null;
        if (is_array($eco) && $input->county !== null) {
            // Placeholder: until the lookup exists, never flag.
            // Test coverage already in place via the ADJ_ALTITUDE_OUT_OF_RANGE
            // const so wiring lands cleanly in P4.
        }

        return $applied;
    }

    /**
     * @param  list<InputPlan>  $inputs
     */
    private function sumCost(array $inputs): float
    {
        $total = 0.0;
        foreach ($inputs as $i) {
            if ($i->costEstimateKes !== null) {
                $total += $i->costEstimateKes;
            }
        }

        return round($total, 2);
    }

    /**
     * Helper for tests + observer: build a SeasonEngineInput from a Season
     * model + its loaded crop relation. Keeps the engine pure but spares
     * callers the manual DTO construction.
     */
    public static function inputFromSeason(Season $season): SeasonEngineInput
    {
        $crop = $season->crop;
        if ($crop === null) {
            throw new InvalidArgumentException("Season {$season->id} has no Crop loaded.");
        }

        return new SeasonEngineInput(
            cropSlug: $crop->slug,
            tenantId: (string) $season->tenant_id,
            seasonId: (string) $season->id,
            acreage: (float) $season->acreage,
            plantingDate: CarbonImmutable::parse((string) $season->planting_date),
            irrigationType: (string) $season->irrigation_type,
        );
    }
}
