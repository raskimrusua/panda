<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('persists with all fillable fields', function () {
    $tenant = Tenant::factory()->create([
        'name' => 'Mwea Vegetables',
        'slug' => 'mwea-vegetables-test',
        'county' => 'Kirinyaga',
        'sub_county' => 'Mwea',
        'ward' => 'Tebere',
        'gps_lat' => -0.6892,
        'gps_lng' => 37.3725,
        'settings' => ['language' => 'sw'],
    ]);

    expect($tenant->name)->toBe('Mwea Vegetables')
        ->and($tenant->county)->toBe('Kirinyaga')
        ->and($tenant->settings)->toBe(['language' => 'sw'])
        ->and($tenant->gps_lat)->toBe(-0.6892);
});

it('uses ULID primary key', function () {
    $tenant = Tenant::factory()->create();

    expect($tenant->id)->toBeString()->toHaveLength(26);
});

it('soft deletes', function () {
    $tenant = Tenant::factory()->create();
    $id = $tenant->id;

    $tenant->delete();

    expect(Tenant::find($id))->toBeNull()
        ->and(Tenant::withTrashed()->find($id))->not->toBeNull();
});

it('logs activity on create + update', function () {
    $tenant = Tenant::factory()->create(['name' => 'Original Name']);

    $tenant->update(['name' => 'Updated Name']);

    $logs = Activity::where('subject_type', Tenant::class)
        ->where('subject_id', $tenant->id)
        ->get();

    expect($logs)->toHaveCount(2)
        ->and($logs->first()->description)->toBe('created')
        ->and($logs->last()->description)->toBe('updated');
});

it('has many users', function () {
    $tenant = Tenant::factory()->create();
    User::factory()->count(3)->create(['tenant_id' => $tenant->id]);

    expect($tenant->users)->toHaveCount(3);
});

it('exposes Spatie current() static helper', function () {
    $tenant = Tenant::factory()->create();
    $tenant->makeCurrent();

    expect(Tenant::current()?->id)->toBe($tenant->id);

    Tenant::forgetCurrent();
    expect(Tenant::current())->toBeNull();
});

it('meru factory state sets the right county', function () {
    $tenant = Tenant::factory()->meru()->create();

    expect($tenant->county)->toBe('Meru')
        ->and($tenant->sub_county)->toBe('Imenti North');
});
