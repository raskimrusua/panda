<?php

use App\Models\Crop;
use App\Models\HarvestLog;
use App\Models\Season;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function actingAsHarvestFarmer(): array
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    test()->actingAs($user);

    return [$tenant, $user];
}

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/v1/harvests')->assertUnauthorized();
});

it('lists own-tenant harvests only', function () {
    [$tenant] = actingAsHarvestFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);
    HarvestLog::factory()->count(3)->create([
        'tenant_id' => $tenant->id, 'season_id' => $season->id,
    ]);

    $other = Tenant::factory()->create();
    $otherSeason = Season::factory()->create(['tenant_id' => $other->id]);
    HarvestLog::factory()->count(2)->create([
        'tenant_id' => $other->id, 'season_id' => $otherSeason->id,
    ]);

    $this->getJson('/api/v1/harvests')->assertOk()->assertJsonCount(3, 'data');
});

it('creates a harvest log', function () {
    [$tenant] = actingAsHarvestFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);

    $this->postJson('/api/v1/harvests', [
        'season_id' => $season->id,
        'harvested_at' => '2026-04-15',
        'quantity_kg' => 100,
        'sold_quantity_kg' => 80,
        'unit_price_kes' => 60,
        'buyer_name' => 'Marikiti Wakulima Group',
    ])->assertCreated()
        ->assertJsonPath('data.quantity_kg', '100.00')
        ->assertJsonPath('data.revenue_kes', 4800);
});

it('rejects sold_quantity_kg greater than quantity_kg', function () {
    [$tenant] = actingAsHarvestFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);

    $this->postJson('/api/v1/harvests', [
        'season_id' => $season->id,
        'harvested_at' => '2026-04-15',
        'quantity_kg' => 50,
        'sold_quantity_kg' => 60,
        'unit_price_kes' => 60,
    ])->assertUnprocessable()->assertJsonValidationErrors(['sold_quantity_kg']);
});

it('rejects unit_price_kes when sold_quantity_kg is set but price missing', function () {
    [$tenant] = actingAsHarvestFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);

    $this->postJson('/api/v1/harvests', [
        'season_id' => $season->id,
        'harvested_at' => '2026-04-15',
        'quantity_kg' => 100,
        'sold_quantity_kg' => 50,
        // unit_price_kes missing
    ])->assertUnprocessable()->assertJsonValidationErrors(['unit_price_kes']);
});

it('store is idempotent on client_id', function () {
    [$tenant] = actingAsHarvestFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);
    $clientId = (string) Str::ulid();

    $first = $this->postJson('/api/v1/harvests', [
        'season_id' => $season->id,
        'harvested_at' => '2026-04-15',
        'quantity_kg' => 10,
        'client_id' => $clientId,
    ])->assertCreated()->json('data.id');

    $second = $this->postJson('/api/v1/harvests', [
        'season_id' => $season->id,
        'harvested_at' => '2026-04-15',
        'quantity_kg' => 10,
        'client_id' => $clientId,
    ])->assertOk()->json('data.id');

    expect($first)->toBe($second)
        ->and(HarvestLog::withoutGlobalScopes()->count())->toBe(1);
});

it('observer recomputes Season.engine_metadata totals after harvest log create', function () {
    [$tenant] = actingAsHarvestFarmer();
    $crop = Crop::factory()->tomato()->create();
    $season = Season::create([
        'tenant_id' => $tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => 'rainfed',
    ]);

    HarvestLog::factory()->create([
        'tenant_id' => $tenant->id,
        'season_id' => $season->id,
        'quantity_kg' => 50,
        'sold_quantity_kg' => 40,
        'unit_price_kes' => 80,
    ]);
    HarvestLog::factory()->create([
        'tenant_id' => $tenant->id,
        'season_id' => $season->id,
        'quantity_kg' => 30,
        'sold_quantity_kg' => 30,
        'unit_price_kes' => 90,
    ]);

    /** @var array<string, mixed> $meta */
    $meta = (array) $season->fresh()->engine_metadata;

    expect($meta)->toHaveKeys(['harvest_total_kg', 'harvest_sold_kg', 'harvest_revenue_kes', 'harvest_log_count'])
        ->and((float) $meta['harvest_total_kg'])->toBe(80.0)
        ->and((float) $meta['harvest_sold_kg'])->toBe(70.0)
        ->and((float) $meta['harvest_revenue_kes'])->toBe(40.0 * 80 + 30.0 * 90)
        ->and($meta['harvest_log_count'])->toBe(2);
});

it('observer recomputes after delete (subtracts from totals)', function () {
    [$tenant] = actingAsHarvestFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);

    $log = HarvestLog::factory()->create([
        'tenant_id' => $tenant->id,
        'season_id' => $season->id,
        'quantity_kg' => 100,
        'sold_quantity_kg' => 100,
        'unit_price_kes' => 50,
    ]);

    /** @var array<string, mixed> $afterCreate */
    $afterCreate = (array) $season->fresh()->engine_metadata;
    expect((float) $afterCreate['harvest_total_kg'])->toBe(100.0);

    $log->delete();

    /** @var array<string, mixed> $afterDelete */
    $afterDelete = (array) $season->fresh()->engine_metadata;
    expect((float) $afterDelete['harvest_total_kg'])->toBe(0.0)
        ->and($afterDelete['harvest_log_count'])->toBe(0);
});

it('CROSS-TENANT: cannot read another tenant harvest (404)', function () {
    actingAsHarvestFarmer();
    $other = Tenant::factory()->create();
    $otherSeason = Season::factory()->create(['tenant_id' => $other->id]);
    $foreign = HarvestLog::factory()->create([
        'tenant_id' => $other->id, 'season_id' => $otherSeason->id,
    ]);

    $this->getJson("/api/v1/harvests/{$foreign->id}")->assertNotFound();
});
