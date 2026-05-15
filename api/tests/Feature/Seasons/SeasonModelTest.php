<?php

use App\Models\Crop;
use App\Models\Season;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('persists with all fillable fields', function () {
    $tenant = Tenant::factory()->create();
    $crop = Crop::factory()->tomato()->create();

    $season = Season::factory()->create([
        'tenant_id' => $tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 1.5,
        'planting_date' => '2026-06-01',
        'status' => Season::STATUS_PLANNING,
        'irrigation_type' => Season::IRRIGATION_DRIP,
    ]);

    expect($season->id)->toBeString()->toHaveLength(26)
        ->and((float) $season->acreage)->toBe(1.5)
        ->and($season->status)->toBe('planning')
        ->and($season->irrigation_type)->toBe('drip')
        ->and($season->planting_date->toDateString())->toBe('2026-06-01');
});

it('belongs to a Crop and a Tenant', function () {
    $crop = Crop::factory()->kale()->create();
    $season = Season::factory()->for($crop)->create();

    expect($season->crop->slug)->toBe('kale')
        ->and($season->tenant)->not->toBeNull();
});

it('soft deletes', function () {
    $season = Season::factory()->create();
    $id = $season->id;

    $season->delete();

    // No current tenant set in this test → TenantScope is a no-op.
    // SoftDeletingScope hides the row; withTrashed() reveals it.
    expect(Season::find($id))->toBeNull()
        ->and(Season::withTrashed()->find($id))->not->toBeNull();
});

it('logs activity on create + update', function () {
    $season = Season::factory()->create(['status' => Season::STATUS_PLANNING]);
    $season->update(['status' => Season::STATUS_ACTIVE]);

    // Order by `id` explicitly: Postgres has no implicit insertion order,
    // and ULIDs generated within the same ms can sort either way. Without
    // an explicit orderBy, this assertion was flaky (same family as the
    // ContentReviewModelTest fix shipped in PR #17).
    $logs = Activity::where('subject_type', Season::class)
        ->where('subject_id', $season->id)
        ->orderBy('id')
        ->get();

    expect($logs)->toHaveCount(2)
        ->and($logs->first()->description)->toBe('created')
        ->and($logs->last()->description)->toBe('updated');
});

it('active scope returns only active + harvesting seasons', function () {
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();

    Season::factory()->planning()->create(['tenant_id' => $tenant->id]);
    Season::factory()->create(['tenant_id' => $tenant->id]); // active default
    Season::factory()->harvesting()->create(['tenant_id' => $tenant->id]);
    Season::factory()->complete()->create(['tenant_id' => $tenant->id]);

    expect(Season::active()->count())->toBe(2);

    Tenant::forgetCurrent();
});

it('auto-attaches tenant_id from the current tenant when omitted', function () {
    $tenant = Tenant::factory()->create();
    $crop = Crop::factory()->tomato()->create();

    $tenant->makeCurrent();

    $season = Season::create([
        'crop_id' => $crop->id,
        'acreage' => 1.0,
        'planting_date' => '2026-07-01',
    ]);

    expect($season->tenant_id)->toBe($tenant->id);

    Tenant::forgetCurrent();
});

it('global scope filters reads to the current tenant', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    Season::factory()->count(2)->create(['tenant_id' => $tenantA->id]);
    Season::factory()->count(3)->create(['tenant_id' => $tenantB->id]);

    $tenantA->makeCurrent();
    expect(Season::count())->toBe(2);

    $tenantB->makeCurrent();
    expect(Season::count())->toBe(3);

    Tenant::forgetCurrent();
    // No current tenant -> scope no-op, sees everything
    expect(Season::count())->toBe(5);
});
