<?php

use App\Models\Crop;
use App\Models\MarketPrice;
use Database\Seeders\MarketPriceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('latest endpoint is public (no auth required)', function () {
    $crop = Crop::factory()->tomato()->create();
    MarketPrice::factory()->create([
        'crop_id' => $crop->id,
        'market_name' => 'Marikiti (Nairobi)',
        'observed_at' => now()->subDays(2)->toDateString(),
    ]);

    $this->getJson("/api/v1/prices/{$crop->slug}/latest")
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['crop_slug', 'market_count']]);
});

it('latest returns most recent price per market', function () {
    $crop = Crop::factory()->tomato()->create();
    MarketPrice::factory()->create([
        'crop_id' => $crop->id,
        'market_name' => 'Marikiti (Nairobi)',
        'observed_at' => now()->subDays(7)->toDateString(),
        'price_per_kg_kes' => 50,
    ]);
    $latest = MarketPrice::factory()->create([
        'crop_id' => $crop->id,
        'market_name' => 'Marikiti (Nairobi)',
        'observed_at' => now()->subDays(2)->toDateString(),
        'price_per_kg_kes' => 80,
    ]);
    MarketPrice::factory()->create([
        'crop_id' => $crop->id,
        'market_name' => 'Karatina (Nyeri)',
        'observed_at' => now()->subDays(2)->toDateString(),
        'price_per_kg_kes' => 75,
    ]);

    $response = $this->getJson("/api/v1/prices/{$crop->slug}/latest")->assertOk();

    expect($response->json('meta.market_count'))->toBe(2);

    $marikiti = collect($response->json('data'))->firstWhere('market_name', 'Marikiti (Nairobi)');
    expect((float) $marikiti['price_per_kg_kes'])->toBe(80.0);
});

it('history returns full series ordered by date', function () {
    $crop = Crop::factory()->tomato()->create();

    foreach ([10, 5, 1] as $daysAgo) {
        MarketPrice::factory()->create([
            'crop_id' => $crop->id,
            'market_name' => 'Marikiti (Nairobi)',
            'observed_at' => now()->subDays($daysAgo)->toDateString(),
        ]);
    }

    $response = $this->getJson("/api/v1/prices/{$crop->slug}/history")->assertOk();

    expect($response->json('meta.observation_count'))->toBe(3);

    $dates = collect($response->json('data'))->pluck('observed_at')->all();
    $sorted = $dates;
    sort($sorted);
    expect($dates)->toBe($sorted);
});

it('forecast returns 3 forward months', function () {
    $crop = Crop::factory()->tomato()->create();

    foreach (range(1, 12) as $monthsAgo) {
        MarketPrice::factory()->create([
            'crop_id' => $crop->id,
            'market_name' => 'Marikiti (Nairobi)',
            'observed_at' => now()->subMonthsNoOverflow($monthsAgo)->toDateString(),
        ]);
    }

    $response = $this->getJson("/api/v1/prices/{$crop->slug}/forecast")->assertOk();

    expect($response->json('data'))->toHaveCount(3)
        ->and($response->json('meta.method'))->toBe('monthly_average');
});

it('forecast returns empty when no history', function () {
    $crop = Crop::factory()->tomato()->create();

    $this->getJson("/api/v1/prices/{$crop->slug}/forecast")
        ->assertOk()
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.note', 'No history available.');
});

it('seeder lands 12-month series for all 5 Phase-1 crops at 6 markets', function () {
    foreach (['tomato', 'kale', 'cabbage', 'bulb-onion', 'french-beans'] as $slug) {
        Crop::factory()->create(['slug' => $slug, 'name_en' => ucfirst($slug), 'name_sw' => $slug]);
    }

    (new MarketPriceSeeder)->run();

    // 5 crops × 6 markets × ~52 weekly observations ≈ 1560 rows
    expect(MarketPrice::count())->toBeGreaterThan(1500);

    // Idempotent — re-running doesn't grow the table
    $count = MarketPrice::count();
    (new MarketPriceSeeder)->run();
    expect(MarketPrice::count())->toBe($count);
});
