<?php

namespace Database\Seeders;

use App\Models\Crop;
use App\Models\MarketPrice;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * 12 months of historical market prices for the 5 JAICA Phase-1 crops at
 * 6 Kenyan markets. Seeded with deterministic seasonal swings around a
 * crop-specific base price so the latest/history endpoints have realistic
 * data to query.
 *
 * Seasonal model (rule-based, JAICA-inspired):
 *   - Tomato + Kale + French Beans: 30% swing, peaks Jul-Sep (dry season),
 *     trough Apr-Jun (long rains glut).
 *   - Cabbage: 20% swing, peaks Dec-Feb, trough Aug-Oct.
 *   - Bulb Onion: 25% swing, peaks Mar-May, trough Sep-Nov.
 *
 * Idempotent — uses unique constraint on (crop_id, market_name,
 * observed_at, grade) so re-running the seeder updates not duplicates.
 */
class MarketPriceSeeder extends Seeder
{
    /** @var array<string, array{base: float, swing: float, peak_month: int}> */
    private const CROP_PRICE_MODEL = [
        'tomato' => ['base' => 70.0, 'swing' => 0.30, 'peak_month' => 8],
        'kale' => ['base' => 35.0, 'swing' => 0.30, 'peak_month' => 8],
        'french-beans' => ['base' => 90.0, 'swing' => 0.30, 'peak_month' => 8],
        'cabbage' => ['base' => 25.0, 'swing' => 0.20, 'peak_month' => 1],
        'bulb-onion' => ['base' => 110.0, 'swing' => 0.25, 'peak_month' => 4],
    ];

    /** @var list<array{name: string, county: string}> */
    private const MARKETS = [
        ['name' => 'Marikiti (Nairobi)', 'county' => 'Nairobi'],
        ['name' => 'Wakulima (Nairobi)', 'county' => 'Nairobi'],
        ['name' => 'Karatina (Nyeri)', 'county' => 'Nyeri'],
        ['name' => 'Kongowea (Mombasa)', 'county' => 'Mombasa'],
        ['name' => 'Eldoret (Uasin Gishu)', 'county' => 'Uasin Gishu'],
        ['name' => 'Kibuye (Kisumu)', 'county' => 'Kisumu'],
    ];

    public function run(): void
    {
        $today = CarbonImmutable::today();
        $now = now();

        foreach (array_keys(self::CROP_PRICE_MODEL) as $slug) {
            $crop = Crop::query()->where('slug', $slug)->first();
            if ($crop === null) {
                continue; // crop not seeded — skip silently
            }

            $model = self::CROP_PRICE_MODEL[$slug];

            $rows = [];
            for ($daysAgo = 360; $daysAgo >= 0; $daysAgo -= 7) {
                $observed = $today->subDays($daysAgo);

                foreach (self::MARKETS as $market) {
                    $price = $this->seasonalPrice(
                        base: $model['base'],
                        swing: $model['swing'],
                        peakMonth: $model['peak_month'],
                        month: (int) $observed->format('n'),
                        marketJitter: ($market['name'] === 'Kongowea (Mombasa)' ? 1.10 : 1.0),
                    );

                    $rows[] = [
                        'id' => (string) Str::ulid(),
                        'crop_id' => $crop->id,
                        'market_name' => $market['name'],
                        'observed_at' => $observed->toDateString(),
                        'grade' => 'standard',
                        'county' => $market['county'],
                        'price_per_kg_kes' => round($price, 2),
                        'source' => MarketPrice::SOURCE_ADMIN_CSV,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            // insertOrIgnore is idempotent against the unique
            // (crop_id, market_name, observed_at, grade) constraint AND
            // sidesteps Eloquent's date-cast lookup-vs-insert format
            // mismatch (date-only string in lookup, datetime in storage)
            // that broke updateOrCreate on SQLite.
            MarketPrice::query()->insertOrIgnore($rows);
        }
    }

    /**
     * Cosine-style seasonal swing — peaks at peak_month, troughs 6 months
     * later, swing percentage controls amplitude.
     */
    private function seasonalPrice(float $base, float $swing, int $peakMonth, int $month, float $marketJitter): float
    {
        $monthsFromPeak = $month - $peakMonth;
        // cos(0) = 1 at peak, cos(pi) = -1 at trough (6 months away)
        $factor = 1 + ($swing * cos(M_PI * $monthsFromPeak / 6));

        return $base * $factor * $marketJitter;
    }
}
