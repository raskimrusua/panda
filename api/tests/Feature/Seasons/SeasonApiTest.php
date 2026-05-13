<?php

use App\Models\Crop;
use App\Models\Season;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: Tenant, 1: User}
 */
function actingAsFarmer(): array
{
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    test()->actingAs($user);

    return [$tenant, $user];
}

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/v1/seasons')->assertUnauthorized();
});

it('lists own-tenant seasons only', function () {
    [$tenant] = actingAsFarmer();
    Season::factory()->count(3)->create(['tenant_id' => $tenant->id]);

    $other = Tenant::factory()->create();
    Season::factory()->count(2)->create(['tenant_id' => $other->id]);

    $this->getJson('/api/v1/seasons')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('creates a season for the current tenant', function () {
    [$tenant] = actingAsFarmer();
    $crop = Crop::factory()->tomato()->create();

    $payload = [
        'crop_id' => $crop->id,
        'acreage' => 1.25,
        'planting_date' => '2026-06-15',
        'status' => Season::STATUS_PLANNING,
        'irrigation_type' => Season::IRRIGATION_DRIP,
    ];

    $response = $this->postJson('/api/v1/seasons', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.crop_id', $crop->id)
        ->assertJsonPath('data.status', 'planning');

    expect(Season::withoutGlobalScopes()->count())->toBe(1)
        ->and(Season::withoutGlobalScopes()->first()->tenant_id)->toBe($tenant->id);
});

it('rejects invalid acreage', function () {
    actingAsFarmer();
    $crop = Crop::factory()->tomato()->create();

    $this->postJson('/api/v1/seasons', [
        'crop_id' => $crop->id,
        'acreage' => -1,
        'planting_date' => '2026-06-15',
    ])->assertUnprocessable()->assertJsonValidationErrors(['acreage']);
});

it('shows a single season belonging to the current tenant', function () {
    [$tenant] = actingAsFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);

    $this->getJson("/api/v1/seasons/{$season->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $season->id);
});

it('updates a season status', function () {
    [$tenant] = actingAsFarmer();
    $season = Season::factory()->planning()->create(['tenant_id' => $tenant->id]);

    $this->patchJson("/api/v1/seasons/{$season->id}", ['status' => Season::STATUS_ACTIVE])
        ->assertOk()
        ->assertJsonPath('data.status', 'active');
});

it('soft deletes a season', function () {
    [$tenant] = actingAsFarmer();
    $season = Season::factory()->create(['tenant_id' => $tenant->id]);

    $this->deleteJson("/api/v1/seasons/{$season->id}")->assertNoContent();

    expect(Season::find($season->id))->toBeNull()
        ->and(Season::withoutGlobalScopes()->withTrashed()->find($season->id))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| MANDATORY cross-tenant isolation tests (skill-laravel-multitenancy)
|--------------------------------------------------------------------------
*/

it('CROSS-TENANT: cannot list another tenant seasons', function () {
    [$tenant] = actingAsFarmer();
    Season::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    $other = Tenant::factory()->create();
    Season::factory()->count(5)->create(['tenant_id' => $other->id]);

    $this->getJson('/api/v1/seasons')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('CROSS-TENANT: cannot read a season belonging to another tenant (404, not 403)', function () {
    actingAsFarmer();

    $other = Tenant::factory()->create();
    $foreign = Season::factory()->create(['tenant_id' => $other->id]);

    $this->getJson("/api/v1/seasons/{$foreign->id}")
        ->assertNotFound();
});

it('CROSS-TENANT: cannot update a season belonging to another tenant (404)', function () {
    actingAsFarmer();

    $other = Tenant::factory()->create();
    $foreign = Season::factory()->create(['tenant_id' => $other->id]);

    $this->patchJson("/api/v1/seasons/{$foreign->id}", ['status' => Season::STATUS_COMPLETE])
        ->assertNotFound();

    expect($foreign->fresh()->status)->not->toBe(Season::STATUS_COMPLETE);
});

it('CROSS-TENANT: store auto-attaches the caller tenant, not a tenant_id supplied in payload', function () {
    [$tenant] = actingAsFarmer();
    $crop = Crop::factory()->tomato()->create();
    $other = Tenant::factory()->create();

    $this->postJson('/api/v1/seasons', [
        'crop_id' => $crop->id,
        'acreage' => 0.5,
        'planting_date' => '2026-08-01',
        'tenant_id' => $other->id, // attempted hijack — must be ignored
    ])->assertCreated();

    expect(Season::withoutGlobalScopes()->count())->toBe(1)
        ->and(Season::withoutGlobalScopes()->first()->tenant_id)->toBe($tenant->id);
});

it('CROSS-TENANT: cannot soft-delete a season belonging to another tenant (404)', function () {
    actingAsFarmer();

    $other = Tenant::factory()->create();
    $foreign = Season::factory()->create(['tenant_id' => $other->id]);

    $this->deleteJson("/api/v1/seasons/{$foreign->id}")
        ->assertNotFound();

    expect(Season::withoutGlobalScopes()->find($foreign->id))->not->toBeNull();
});
