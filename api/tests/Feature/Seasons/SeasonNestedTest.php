<?php

use App\Models\CostEntry;
use App\Models\Crop;
use App\Models\HarvestLog;
use App\Models\Season;
use App\Models\SeasonActivity;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->crop = Crop::factory()->tomato()->create();

    $this->tenant->makeCurrent();
    $this->season = Season::create([
        'tenant_id' => $this->tenant->id,
        'crop_id' => $this->crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => 'rainfed',
    ]);
    Tenant::forgetCurrent();

    $this->actingAs($this->user);
});

it('GET /seasons/{id}/timeline returns engine-generated activities ordered by date', function () {
    $response = $this->getJson("/api/v1/seasons/{$this->season->id}/timeline")
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'activity_type', 'ideal_date', 'status']]]);

    $dates = collect($response->json('data'))->pluck('ideal_date')->all();
    $sorted = $dates;
    sort($sorted);
    expect($dates)->toBe($sorted);
});

it('GET /seasons/{id}/input-list returns scaled inputs', function () {
    $this->getJson("/api/v1/seasons/{$this->season->id}/input-list")
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'product_name', 'quantity_scaled', 'unit']]]);
});

it('GET /seasons/{id}/costs returns entries grouped by category + total', function () {
    CostEntry::factory()->create([
        'tenant_id' => $this->tenant->id,
        'season_id' => $this->season->id,
        'category' => CostEntry::CATEGORY_SEED,
        'amount_kes' => 4500,
    ]);
    CostEntry::factory()->create([
        'tenant_id' => $this->tenant->id,
        'season_id' => $this->season->id,
        'category' => CostEntry::CATEGORY_FERTILISER,
        'amount_kes' => 7000,
    ]);

    $this->getJson("/api/v1/seasons/{$this->season->id}/costs")
        ->assertOk()
        ->assertJsonPath('totals.all_kes', 11500)
        ->assertJsonPath('totals.by_category.seed', 4500)
        ->assertJsonPath('totals.by_category.fertiliser', 7000);
});

it('GET /seasons/{id}/harvests returns rolling totals + revenue', function () {
    HarvestLog::factory()->create([
        'tenant_id' => $this->tenant->id,
        'season_id' => $this->season->id,
        'quantity_kg' => 100,
        'sold_quantity_kg' => 80,
        'unit_price_kes' => 60,
    ]);

    $this->getJson("/api/v1/seasons/{$this->season->id}/harvests")
        ->assertOk()
        ->assertJsonPath('totals.quantity_kg', 100)
        ->assertJsonPath('totals.sold_quantity_kg', 80)
        ->assertJsonPath('totals.revenue_kes', 4800);
});

it('GET /seasons/{id}/report returns a downloadable PDF', function () {
    CostEntry::factory()->create([
        'tenant_id' => $this->tenant->id,
        'season_id' => $this->season->id,
        'amount_kes' => 5000,
    ]);
    HarvestLog::factory()->create([
        'tenant_id' => $this->tenant->id,
        'season_id' => $this->season->id,
        'quantity_kg' => 50,
        'sold_quantity_kg' => 40,
        'unit_price_kes' => 80,
    ]);

    $response = $this->get("/api/v1/seasons/{$this->season->id}/report");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('panda-season-tomato');
    expect($response->getContent())->toStartWith('%PDF-');
});

it('GET /seasons/{id}/costs.csv streams a CSV of cost entries', function () {
    CostEntry::factory()->create([
        'tenant_id' => $this->tenant->id,
        'season_id' => $this->season->id,
        'category' => CostEntry::CATEGORY_SEED,
        'description' => 'Hybrid seed bag',
        'amount_kes' => 4500,
        'incurred_at' => '2026-06-02',
        'supplier_name' => 'Kenya Seed Co',
    ]);

    $response = $this->get("/api/v1/seasons/{$this->season->id}/costs.csv");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('panda-season-tomato-costs');

    $body = $response->streamedContent();
    expect($body)->toContain('incurred_at,category,description,amount_kes,supplier_name');
    expect($body)->toContain('2026-06-02,seed,"Hybrid seed bag",4500.00,"Kenya Seed Co"');
});

it('GET /seasons/{id}/harvests.csv streams a CSV with computed revenue', function () {
    HarvestLog::factory()->create([
        'tenant_id' => $this->tenant->id,
        'season_id' => $this->season->id,
        'quantity_kg' => 100,
        'sold_quantity_kg' => 80,
        'unit_price_kes' => 60,
        'harvested_at' => '2026-09-15',
        'buyer_name' => 'Wakulima Market',
    ]);

    $response = $this->get("/api/v1/seasons/{$this->season->id}/harvests.csv");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('panda-season-tomato-harvests');

    $body = $response->streamedContent();
    expect($body)->toContain('harvested_at,quantity_kg,sold_quantity_kg,unit_price_kes,revenue_kes,buyer_name');
    expect($body)->toContain('2026-09-15,100.00,80.00,60.00,4800.00,"Wakulima Market"');
});

it('GET /seasons/{id}/activities.csv streams a CSV of timeline activities', function () {
    // Engine-generated activities exist after Season creation; just hit the endpoint.
    $response = $this->get("/api/v1/seasons/{$this->season->id}/activities.csv");

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->headers->get('content-disposition'))->toContain('panda-season-tomato-activities');

    $body = $response->streamedContent();
    expect($body)->toContain('ideal_date,phase,activity_type,description_en,is_critical,status,completed_at');

    // At least one engine activity should appear; first column is the date.
    $first = SeasonActivity::query()->where('season_id', $this->season->id)->orderBy('ideal_date')->first();
    if ($first) {
        expect($body)->toContain($first->ideal_date->toDateString());
    }
});

it('CROSS-TENANT: cannot fetch nested endpoints for another tenant Season (404)', function () {
    $other = Tenant::factory()->create();
    $other->makeCurrent();
    $foreignSeason = Season::create([
        'tenant_id' => $other->id,
        'crop_id' => $this->crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => 'rainfed',
    ]);
    Tenant::forgetCurrent();

    $endpoints = [
        'timeline', 'input-list', 'costs', 'harvests', 'report',
        'costs.csv', 'harvests.csv', 'activities.csv',
    ];
    foreach ($endpoints as $endpoint) {
        $this->get("/api/v1/seasons/{$foreignSeason->id}/{$endpoint}")
            ->assertNotFound();
    }
});
