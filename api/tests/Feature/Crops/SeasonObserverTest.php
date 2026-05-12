<?php

use App\Models\Crop;
use App\Models\InputListItem;
use App\Models\Season;
use App\Models\SeasonActivity;
use App\Models\Tenant;
use App\Services\Crops\SeasonEngine\SeasonEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->tenant->makeCurrent();
});

afterEach(function () {
    Tenant::forgetCurrent();
});

it('creating a Season auto-generates SeasonActivity rows from the engine', function () {
    $crop = Crop::factory()->tomato()->create();

    $season = Season::create([
        'tenant_id' => $this->tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => SeasonEngine::IRRIGATION_RAINFED,
    ]);

    $count = SeasonActivity::where('season_id', $season->id)->count();
    expect($count)->toBeGreaterThanOrEqual(10);
});

it('creating a Season auto-generates InputListItem rows scaled to acreage', function () {
    $crop = Crop::factory()->tomato()->create();

    $oneAcre = Season::create([
        'tenant_id' => $this->tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => SeasonEngine::IRRIGATION_RAINFED,
    ]);
    $threeAcre = Season::create([
        'tenant_id' => $this->tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 3.0,
        'planting_date' => '2026-06-15',
        'irrigation_type' => SeasonEngine::IRRIGATION_RAINFED,
    ]);

    $oneAcreInputs = InputListItem::where('season_id', $oneAcre->id)->orderBy('week_from_planting')->get();
    $threeAcreInputs = InputListItem::where('season_id', $threeAcre->id)->orderBy('week_from_planting')->get();

    expect($oneAcreInputs)->toHaveCount($threeAcreInputs->count())
        ->and($oneAcreInputs)->not->toBeEmpty();

    foreach ($oneAcreInputs as $i => $oneInput) {
        expect((float) $threeAcreInputs[$i]->quantity_scaled)
            ->toEqualWithDelta((float) $oneInput->quantity_scaled * 3, 0.0001);
    }
});

it('Season::activities relation returns the auto-generated rows ordered by ideal_date', function () {
    $crop = Crop::factory()->tomato()->create();
    $season = Season::create([
        'tenant_id' => $this->tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => SeasonEngine::IRRIGATION_RAINFED,
    ]);

    $activities = $season->fresh()->activities;
    $dates = $activities->pluck('ideal_date')->map(fn ($d) => (string) $d)->all();
    $sorted = $dates;
    sort($sorted);

    expect($activities)->not->toBeEmpty()
        ->and($dates)->toBe($sorted);
});

it('engine_metadata is stamped with adjustments + counts after creation', function () {
    $crop = Crop::factory()->tomato()->create();

    $season = Season::create([
        'tenant_id' => $this->tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => SeasonEngine::IRRIGATION_GREENHOUSE,
    ]);

    /** @var array<string, mixed> $meta */
    $meta = (array) $season->fresh()->engine_metadata;

    expect($meta)->toHaveKeys(['engine_run_at', 'adjustments_applied', 'cost_estimate_total_kes', 'activities_generated', 'inputs_generated'])
        ->and($meta['adjustments_applied'])->toContain(SeasonEngine::ADJ_GREENHOUSE_PESTICIDE_CUT)
        ->and($meta['activities_generated'])->toBeGreaterThanOrEqual(10)
        ->and($meta['inputs_generated'])->toBeGreaterThanOrEqual(5);
});

it('engine failure (unknown crop slug) leaves the Season alive but un-generated', function () {
    Log::spy();

    // Create a crop NOT in the content library
    $crop = Crop::factory()->create([
        'slug' => 'nonexistent-test-crop',
        'name_en' => 'Nonexistent',
        'name_sw' => 'Hakuna',
    ]);

    $season = Season::create([
        'tenant_id' => $this->tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => SeasonEngine::IRRIGATION_RAINFED,
    ]);

    expect(Season::find($season->id))->not->toBeNull()
        ->and(SeasonActivity::where('season_id', $season->id)->count())->toBe(0)
        ->and(InputListItem::where('season_id', $season->id)->count())->toBe(0);

    /** @phpstan-ignore-next-line method.notFound — Mockery facade method, not statically resolvable */
    Log::shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $msg) => str_contains($msg, 'engine failed'));
});

it('multitenant: activities + inputs created with correct tenant_id', function () {
    $crop = Crop::factory()->tomato()->create();
    $season = Season::create([
        'tenant_id' => $this->tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => SeasonEngine::IRRIGATION_RAINFED,
    ]);

    $activity = SeasonActivity::where('season_id', $season->id)->first();
    $input = InputListItem::where('season_id', $season->id)->first();

    expect($activity?->tenant_id)->toBe($this->tenant->id)
        ->and($input?->tenant_id)->toBe($this->tenant->id);
});
