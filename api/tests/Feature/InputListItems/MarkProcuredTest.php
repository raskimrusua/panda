<?php

use App\Models\Crop;
use App\Models\InputListItem;
use App\Models\Season;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('marks an input as procured', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);
    $crop = Crop::factory()->tomato()->create();

    $tenant->makeCurrent();
    $season = Season::create([
        'tenant_id' => $tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => 'rainfed',
    ]);
    Tenant::forgetCurrent();

    $item = $season->inputListItems()->first();

    $this->actingAs($user)
        ->postJson("/api/v1/input-list-items/{$item->id}/mark-procured", [
            'procured_quantity' => 200,
        ])
        ->assertOk()
        ->assertJsonPath('data.procured_quantity', '200.0000');

    $fresh = InputListItem::find($item->id);
    expect((float) $fresh->procured_quantity)->toBe(200.0)
        ->and($fresh->procured_at)->not->toBeNull();
});

it('CROSS-TENANT: cannot mark-procured on another tenant input (404)', function () {
    $owner = Tenant::factory()->create();
    $crop = Crop::factory()->tomato()->create();

    $owner->makeCurrent();
    $season = Season::create([
        'tenant_id' => $owner->id,
        'crop_id' => $crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-06-01',
        'irrigation_type' => 'rainfed',
    ]);
    Tenant::forgetCurrent();

    $item = $season->inputListItems()->first();

    $intruder = User::factory()->create();
    $this->actingAs($intruder)
        ->postJson("/api/v1/input-list-items/{$item->id}/mark-procured", [
            'procured_quantity' => 999,
        ])
        ->assertNotFound();

    $fresh = InputListItem::withoutGlobalScopes()->find($item->id);
    expect($fresh->procured_quantity)->toBeNull();
});
