<?php

declare(strict_types=1);

namespace App\Services\Prices;

use App\Models\Crop;
use App\Models\MarketPrice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Imports the normalized price CSV contract into the append-only
 * market_prices table. See docs/panda-price-feed-design.md for the contract.
 *
 * Rules: one row = one (crop, market, date, grade) observation; existing
 * tuples are skipped (idempotent re-runs insert nothing); unmatched crop
 * names are reported, never silently dropped; non-numeric or non-positive
 * prices and non-Y-m-d dates are rejected and counted.
 */
final class PriceCsvImporter
{
    /** @var list<string> */
    private const REQUIRED_HEADERS = ['crop', 'market', 'county', 'date', 'price_per_kg'];

    /**
     * @return array{imported: int, skipped: int, invalid: int, unmatched: int, unmatched_sample: list<string>, source: string}
     */
    public function import(string $csv, string $source = MarketPrice::SOURCE_AMIS_KENYA): array
    {
        ['rows' => $rows, 'invalid' => $invalid] = $this->parse($csv);

        $report = [
            'imported' => 0,
            'skipped' => 0,
            'invalid' => $invalid,
            'unmatched' => 0,
            'unmatched_sample' => [],
            'source' => $source,
        ];

        if ($rows === []) {
            return $report;
        }

        $slugs = array_values(array_unique(array_map(
            fn (array $r): string => (string) CropAliasMap::toSlug($r['crop']),
            $rows
        )));

        /** @var Collection<string, string> $cropIds */
        $cropIds = Crop::query()->whereIn('slug', $slugs)->pluck('id', 'slug');

        $dates = array_values(array_unique(array_column($rows, 'date')));
        $seen = [];

        MarketPrice::query()
            ->where('source', $source)
            ->whereIn('crop_id', $cropIds->values())
            ->whereIn('observed_at', $dates)
            ->get(['crop_id', 'market_name', 'observed_at', 'grade'])
            ->each(function (MarketPrice $p) use (&$seen, $source): void {
                $seen[$this->key((string) $p->crop_id, (string) $p->market_name, Carbon::parse((string) $p->observed_at)->format('Y-m-d'), (string) $p->grade, $source)] = true;
            });

        foreach ($rows as $row) {
            $cropId = $cropIds[(string) CropAliasMap::toSlug($row['crop'])] ?? null;

            if ($cropId === null) {
                $report['unmatched']++;

                if (! in_array($row['crop'], $report['unmatched_sample'], true) && count($report['unmatched_sample']) < 10) {
                    $report['unmatched_sample'][] = $row['crop'];
                }

                continue;
            }

            $key = $this->key((string) $cropId, $row['market'], $row['date'], $row['grade'] ?? '', $source);

            if (isset($seen[$key])) {
                $report['skipped']++;

                continue;
            }

            $seen[$key] = true;

            MarketPrice::create([
                'crop_id' => $cropId,
                'market_name' => $row['market'],
                'county' => $row['county'],
                'observed_at' => $row['date'],
                'grade' => $row['grade'],
                'price_per_kg_kes' => $row['price'],
                'source' => $source,
            ]);

            $report['imported']++;
        }

        return $report;
    }

    /**
     * @return array{rows: list<array{crop: string, market: string, county: ?string, date: string, grade: ?string, price: float}>, invalid: int}
     */
    private function parse(string $csv): array
    {
        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $csv) ?: []),
            fn (string $l): bool => $l !== ''
        ));

        if ($lines === []) {
            return ['rows' => [], 'invalid' => 0];
        }

        $header = str_getcsv((string) array_shift($lines));

        if (! in_array('crop', $header, true)) {
            return ['rows' => [], 'invalid' => 0];
        }

        $col = array_flip($header);

        foreach (self::REQUIRED_HEADERS as $required) {
            if (! isset($col[$required])) {
                return ['rows' => [], 'invalid' => 0];
            }
        }

        $rows = [];
        $invalid = 0;

        foreach ($lines as $line) {
            $cells = str_getcsv($line);

            $get = function (string $name) use ($cells, $col): ?string {
                if (! isset($col[$name], $cells[$col[$name]])) {
                    return null;
                }

                $value = trim((string) $cells[$col[$name]]);

                return $value === '' ? null : $value;
            };

            $crop = $get('crop');
            $market = $get('market');
            $date = $get('date');
            $priceRaw = $get('price_per_kg');

            if ($crop === null || $market === null || $get('county') === null || $date === null || $priceRaw === null) {
                $invalid++;

                continue;
            }

            if (! is_numeric($priceRaw) || (float) $priceRaw <= 0) {
                $invalid++;

                continue;
            }

            if (! Carbon::hasFormat($date, 'Y-m-d')) {
                $invalid++;

                continue;
            }

            $rows[] = [
                'crop' => $crop,
                'market' => $market,
                'county' => $get('county'),
                'date' => $date,
                'grade' => $get('grade') ?? 'standard',
                'price' => (float) $priceRaw,
            ];
        }

        return ['rows' => $rows, 'invalid' => $invalid];
    }

    private function key(string $cropId, string $market, string $date, string $grade, string $source): string
    {
        return $cropId.'|'.mb_strtolower(trim($market)).'|'.$date.'|'.mb_strtolower(trim($grade)).'|'.$source;
    }
}
