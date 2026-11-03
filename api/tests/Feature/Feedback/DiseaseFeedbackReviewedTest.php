<?php

use App\Models\DiseaseFeedback;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('markReviewed stamps reviewed_at + reviewed_by', function () {
    $tenant = Tenant::factory()->create();
    $reviewer = User::factory()->create(['tenant_id' => $tenant->id]);
    $feedback = DiseaseFeedback::factory()->create(['tenant_id' => $tenant->id]);

    expect($feedback->reviewed_at)->toBeNull()
        ->and($feedback->reviewed_by)->toBeNull();

    $feedback->markReviewed($reviewer);

    expect($feedback->reviewed_at)->not->toBeNull()
        ->and($feedback->reviewed_by)->toBe($reviewer->id);

    $fresh = DiseaseFeedback::withoutGlobalScopes()->find($feedback->id);
    expect($fresh->reviewed_by)->toBe($reviewer->id);
});

it('reviewedBy resolves to the ops user', function () {
    $tenant = Tenant::factory()->create();
    $reviewer = User::factory()->create(['tenant_id' => $tenant->id]);
    $feedback = DiseaseFeedback::factory()->create(['tenant_id' => $tenant->id]);

    $feedback->markReviewed($reviewer);

    expect($feedback->reviewedBy?->id)->toBe($reviewer->id);
});
