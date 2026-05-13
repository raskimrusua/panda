<?php

use App\Models\Crop;
use App\Models\Season;
use App\Models\SeasonActivity;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs an activity as done', function () {
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

    $activity = $season->activities()->first();

    $this->actingAs($user)
        ->postJson("/api/v1/activities/{$activity->id}/log-done", [
            'completion_notes' => 'Done early — soil was already moist.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'done')
        ->assertJsonPath('data.completion_notes', 'Done early — soil was already moist.');

    $fresh = SeasonActivity::find($activity->id);
    expect($fresh->status)->toBe('done')
        ->and($fresh->completed_by)->toBe($user->id)
        ->and($fresh->completed_at)->not->toBeNull();
});

it('CROSS-TENANT: cannot log-done another tenant activity (404)', function () {
    $owner = Tenant::factory()->create();
    $ownerUser = User::factory()->create(['tenant_id' => $owner->id]);
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

    $activity = $season->activities()->first();

    // Different tenant's user tries to mark it done
    $intruder = User::factory()->create();
    $this->actingAs($intruder)
        ->postJson("/api/v1/activities/{$activity->id}/log-done", [])
        ->assertNotFound();

    $fresh = SeasonActivity::withoutGlobalScopes()->find($activity->id);
    expect($fresh->status)->toBe('pending');
});
