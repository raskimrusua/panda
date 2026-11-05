<?php

use App\Models\Crop;
use App\Models\MarketPrice;
use App\Services\Prices\PriceCsvImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const PRICES_CSV = <<<'CSV'
crop,market,county,date,grade,price_per_kg
Tomatoes,Marikiti (Nairobi),Nairobi,2026-11-03,Grade 1,85.50
Dry Maize,Embu Market,Embu,2026-11-03,,52
Sukuma Wiki,Karatina (Nyeri),Nyeri,2026-11-03,,40
Unobtanium Crop,Somewhere Market,Nowhere,2026-11-03,,10
CSV;

beforeEach(function () {
    Crop::factory()->create(['slug' => 'tomato']);
    Crop::factory()->create(['slug' => 'maize']);
    Crop::factory()->create(['slug' => 'kale']);
});

it('imports valid rows and maps crop aliases to slugs', function () {
    $report = (new PriceCsvImporter)->import(PRICES_CSV);

    expect($report['imported'])->toBe(3)
        ->and($report['unmatched'])->toBe(1)
        ->and($report['unmatched_sample'])->toBe(['Unobtanium Crop']);

    expect(MarketPrice::count())->toBe(3);

    $tomato = MarketPrice::whereHas('crop', function ($q): void {
        $q->where('slug', 'tomato');
    })->sole();

    expect((float) $tomato->price_per_kg_kes)->toBe(85.5)
        ->and($tomato->market_name)->toBe('Marikiti (Nairobi)')
        ->and($tomato->county)->toBe('Nairobi')
        ->and($tomato->grade)->toBe('Grade 1')
        ->and($tomato->source)->toBe(MarketPrice::SOURCE_AMIS_KENYA)
        ->and((string) $tomato->observed_at)->toBe('2026-11-03 00:00:00');
});

it('is idempotent — re-running the same file inserts nothing', function () {
    $importer = new PriceCsvImporter;

    expect($importer->import(PRICES_CSV)['imported'])->toBe(3);

    $second = $importer->import(PRICES_CSV);

    expect($second['imported'])->toBe(0)
        ->and($second['skipped'])->toBe(3)
        ->and(MarketPrice::count())->toBe(3);
});

it('rejects invalid prices and dates and counts them', function () {
    $csv = "crop,market,county,date,price_per_kg\n"
        ."Tomatoes,Marikiti (Nairobi),Nairobi,2026-11-03,abc\n"
        ."Tomatoes,Marikiti (Nairobi),Nairobi,not-a-date,50\n"
        ."Tomatoes,Marikiti (Nairobi),Nairobi,2026-11-03,-5\n"
        ."Tomatoes,Marikiti (Nairobi),,2026-11-03,60\n";

    $report = (new PriceCsvImporter)->import($csv);

    expect($report['invalid'])->toBe(4)
        ->and($report['imported'])->toBe(0);
});

it('returns an empty report for empty input', function () {
    $report = (new PriceCsvImporter)->import('');

    expect($report)->toMatchArray([
        'imported' => 0,
        'skipped' => 0,
        'invalid' => 0,
        'unmatched' => 0,
        'unmatched_sample' => [],
    ]);
});
