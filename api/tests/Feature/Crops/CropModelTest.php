<?php

use App\Models\Crop;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('persists with all fillable fields', function () {
    $crop = Crop::factory()->tomato()->create();

    expect($crop->slug)->toBe('tomato')
        ->and($crop->name_en)->toBe('Tomato')
        ->and($crop->name_sw)->toBe('Nyanya')
        ->and($crop->category)->toBe('vegetable')
        ->and($crop->harvest_type)->toBe('multi')
        ->and($crop->is_active)->toBeTrue()
        ->and($crop->phase_added)->toBe(1);
});

it('uses ULID primary keys', function () {
    $crop = Crop::factory()->create();

    expect($crop->id)->toBeString()
        ->and(strlen($crop->id))->toBe(26); // ULID length
});

it('enforces unique slug', function () {
    Crop::factory()->tomato()->create();

    expect(fn () => Crop::factory()->tomato()->create())
        ->toThrow(UniqueConstraintViolationException::class);
});

it('soft-deletes instead of hard-deleting', function () {
    $crop = Crop::factory()->tomato()->create();
    $id = $crop->id;

    $crop->delete();

    expect(Crop::find($id))->toBeNull()                                    // default scope hides
        ->and(Crop::withTrashed()->find($id))->not->toBeNull()             // still in DB
        ->and(Crop::withTrashed()->find($id)->deleted_at)->not->toBeNull(); // marked
});

it('hides deleted_at from default array output', function () {
    $crop = Crop::factory()->tomato()->create();

    expect($crop->toArray())->not->toHaveKey('deleted_at');
});

it('scopes to active crops only', function () {
    Crop::factory()->tomato()->create();
    Crop::factory()->kale()->inactive()->create();

    $active = Crop::active()->get();

    expect($active)->toHaveCount(1)
        ->and($active->first()->slug)->toBe('tomato');
});

it('scopes to crops in current build phase', function () {
    Crop::factory()->tomato()->create();         // phase 1
    Crop::factory()->cabbage()->phase2()->create(); // phase 2

    expect(Crop::inPhase(1)->count())->toBe(1)   // tomato only
        ->and(Crop::inPhase(2)->count())->toBe(2); // both
});

it('logs activity on create + update', function () {
    $crop = Crop::factory()->tomato()->create();

    expect(Activity::forSubject($crop)->where('description', 'created')->count())->toBe(1);

    $crop->update(['name_en' => 'Tomato (renamed)']);

    expect(Activity::forSubject($crop)->where('description', 'updated')->count())->toBe(1)
        ->and(Activity::forSubject($crop)->where('description', 'updated')->latest()->first()->properties['attributes']['name_en'])
        ->toBe('Tomato (renamed)');
});

it('does not log activity for unchanged updates (logOnlyDirty)', function () {
    $crop = Crop::factory()->tomato()->create();

    Activity::forSubject($crop)->delete();

    $crop->update(['name_en' => 'Tomato']); // unchanged

    expect(Activity::forSubject($crop)->count())->toBe(0);
});
