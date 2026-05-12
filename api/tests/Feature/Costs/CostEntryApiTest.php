<?php

use App\Models\CostEntry;
use App\Models\Season;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function actingAsCostFarmer(): array
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    test()->actingAs($user);

    return [$tenant, $user];
}

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/v1/costs')->assertUnauthorized();
});

it('lists own-tenant costs only', function () {
    [$tenant] = actingAsCostFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);
    CostEntry::factory()->count(3)->create([
        'tenant_id' => $tenant->id,
        'season_id' => $season->id,
    ]);

    $other = Tenant::factory()->create();
    $otherSeason = Season::factory()->create(['tenant_id' => $other->id]);
    CostEntry::factory()->count(5)->create([
        'tenant_id' => $other->id,
        'season_id' => $otherSeason->id,
    ]);

    $this->getJson('/api/v1/costs')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a cost entry for the current tenant', function () {
    [$tenant] = actingAsCostFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);

    $this->postJson('/api/v1/costs', [
        'season_id' => $season->id,
        'category' => CostEntry::CATEGORY_SEED,
        'description' => 'Tylka F1 seed pack',
        'amount_kes' => 4500,
        'incurred_at' => '2026-05-10',
        'supplier_name' => 'Elgon Kenya',
    ])->assertCreated()
        ->assertJsonPath('data.category', 'seed')
        ->assertJsonPath('data.amount_kes', '4500.00');

    expect(CostEntry::withoutGlobalScopes()->count())->toBe(1)
        ->and(CostEntry::withoutGlobalScopes()->first()->tenant_id)->toBe($tenant->id);
});

it('rejects negative amount', function () {
    [$tenant] = actingAsCostFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);

    $this->postJson('/api/v1/costs', [
        'season_id' => $season->id,
        'category' => CostEntry::CATEGORY_SEED,
        'description' => 'Bad',
        'amount_kes' => -10,
        'incurred_at' => '2026-05-10',
    ])->assertUnprocessable()->assertJsonValidationErrors(['amount_kes']);
});

it('rejects future incurred_at', function () {
    [$tenant] = actingAsCostFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);

    $this->postJson('/api/v1/costs', [
        'season_id' => $season->id,
        'category' => CostEntry::CATEGORY_SEED,
        'description' => 'Future',
        'amount_kes' => 100,
        'incurred_at' => now()->addDays(7)->toDateString(),
    ])->assertUnprocessable()->assertJsonValidationErrors(['incurred_at']);
});

it('updates a cost entry', function () {
    [$tenant] = actingAsCostFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);
    $cost = CostEntry::factory()->create(['tenant_id' => $tenant->id, 'season_id' => $season->id]);

    $this->patchJson("/api/v1/costs/{$cost->id}", ['description' => 'updated'])
        ->assertOk()
        ->assertJsonPath('data.description', 'updated');
});

it('soft deletes a cost entry', function () {
    [$tenant] = actingAsCostFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);
    $cost = CostEntry::factory()->create(['tenant_id' => $tenant->id, 'season_id' => $season->id]);

    $this->deleteJson("/api/v1/costs/{$cost->id}")->assertNoContent();
    expect(CostEntry::find($cost->id))->toBeNull();
});

/* MANDATORY cross-tenant isolation tests */

it('CROSS-TENANT: cannot read another tenant cost (404)', function () {
    actingAsCostFarmer();
    $other = Tenant::factory()->create();
    $otherSeason = Season::factory()->create(['tenant_id' => $other->id]);
    $foreign = CostEntry::factory()->create(['tenant_id' => $other->id, 'season_id' => $otherSeason->id]);

    $this->getJson("/api/v1/costs/{$foreign->id}")->assertNotFound();
});

it('CROSS-TENANT: cannot update another tenant cost (404)', function () {
    actingAsCostFarmer();
    $other = Tenant::factory()->create();
    $otherSeason = Season::factory()->create(['tenant_id' => $other->id]);
    $foreign = CostEntry::factory()->create(['tenant_id' => $other->id, 'season_id' => $otherSeason->id]);

    $this->patchJson("/api/v1/costs/{$foreign->id}", ['description' => 'hijack'])
        ->assertNotFound();
});
