<?php

use App\Models\Crop;
use App\Models\MarketPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

const COMMAND_CSV = <<<'CSV'
crop,market,county,date,price_per_kg
Tomatoes,Marikiti (Nairobi),Nairobi,2026-11-03,85.50
CSV;

it('imports from a local file', function () {
    Crop::factory()->create(['slug' => 'tomato']);

    $path = tempnam(sys_get_temp_dir(), 'prices').'.csv';
    file_put_contents($path, COMMAND_CSV);

    try {
        $this->artisan('panda:import-prices', ['--file' => $path])
            ->expectsOutputToContain('1 imported')
            ->assertExitCode(0);
    } finally {
        @unlink($path);
    }

    expect(MarketPrice::count())->toBe(1);
});

it('reports not-configured when no file and the flags are off', function () {
    config()->set('prices.feed_enabled', false);
    config()->set('prices.feed_url', null);

    $this->artisan('panda:import-prices')
        ->expectsOutputToContain('not configured')
        ->assertExitCode(0);

    expect(MarketPrice::count())->toBe(0);
});

it('fetches the configured feed URL when the flag is on', function () {
    Crop::factory()->create(['slug' => 'tomato']);

    config()->set('prices.feed_enabled', true);
    config()->set('prices.feed_url', 'https://feed.example.com/prices.csv');

    Http::fake([
        'feed.example.com/*' => Http::response(COMMAND_CSV, 200),
    ]);

    $this->artisan('panda:import-prices')
        ->expectsOutputToContain('1 imported')
        ->assertExitCode(0);

    expect(MarketPrice::count())->toBe(1);
});

it('fails gracefully when the feed URL is unreachable', function () {
    config()->set('prices.feed_enabled', true);
    config()->set('prices.feed_url', 'https://feed.example.com/prices.csv');

    Http::fake([
        'feed.example.com/*' => Http::response('', 500),
    ]);

    $this->artisan('panda:import-prices')
        ->expectsOutputToContain('Could not read the CSV source')
        ->assertExitCode(1);
});
