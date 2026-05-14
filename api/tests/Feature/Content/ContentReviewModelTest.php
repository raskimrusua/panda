<?php

use App\Models\ContentReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;

uses(RefreshDatabase::class);

it('persists with all fillable fields', function () {
    $silas = User::factory()->create(['name' => 'Silas']);
    $reviewer = User::factory()->superuser()->create();

    $review = ContentReview::factory()->create([
        'target_type' => ContentReview::TARGET_CROP,
        'target_slug' => 'tomato',
        'status' => ContentReview::STATUS_SUBMITTED,
        'submitted_by' => $silas->id,
        'reviewer_id' => $reviewer->id,
        'content_payload' => ['varieties' => ['Tylka F1']],
        'submitted_at' => now(),
    ]);

    expect($review->target_slug)->toBe('tomato')
        ->and($review->status)->toBe('submitted')
        ->and($review->content_payload)->toBe(['varieties' => ['Tylka F1']])
        ->and($review->submitter->id)->toBe($silas->id)
        ->and($review->reviewer->id)->toBe($reviewer->id);
});

it('soft deletes', function () {
    $review = ContentReview::factory()->create();
    $id = $review->id;

    $review->delete();

    expect(ContentReview::find($id))->toBeNull()
        ->and(ContentReview::withTrashed()->find($id))->not->toBeNull();
});

it('logs activity on create + status change', function () {
    $review = ContentReview::factory()->create(['status' => ContentReview::STATUS_DRAFT]);
    $review->update(['status' => ContentReview::STATUS_SUBMITTED]);

    // Order by `id` explicitly: Postgres has no implicit insertion order,
    // and ULIDs generated within the same ms can sort either way. Without
    // an explicit orderBy, this assertion was flaky.
    $logs = Activity::where('subject_type', ContentReview::class)
        ->where('subject_id', $review->id)
        ->orderBy('id')
        ->get();

    expect($logs)->toHaveCount(2)
        ->and($logs->first()->description)->toBe('created')
        ->and($logs->last()->description)->toBe('updated');
});

it('pending scope returns only submitted reviews', function () {
    ContentReview::factory()->create();                    // draft
    ContentReview::factory()->submitted()->count(2)->create();
    ContentReview::factory()->approved()->create();
    ContentReview::factory()->changesRequested()->create();

    expect(ContentReview::pending()->count())->toBe(2);
});

it('approved factory state sets reviewer + decided_at', function () {
    $review = ContentReview::factory()->approved()->create();

    expect($review->status)->toBe('approved')
        ->and($review->reviewer_id)->not->toBeNull()
        ->and($review->decided_at)->not->toBeNull();
});

it('forDisease factory state flips target type', function () {
    $review = ContentReview::factory()->forDisease()->create();

    expect($review->target_type)->toBe('disease');
});
