<?php

use App\Models\DiseaseDetection;
use App\Models\DiseaseFeedback;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->actingAs($this->user);
});

it('rejects unauthenticated requests', function () {
    auth()->logout();
    $detection = DiseaseDetection::factory()->create();

    $this->postJson("/api/v1/disease/{$detection->id}/feedback", ['verdict' => 'correct'])
        ->assertUnauthorized();
});

it('stores a verdict with a free-text correction', function () {
    $detection = DiseaseDetection::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->postJson("/api/v1/disease/{$detection->id}/feedback", [
        'verdict' => 'incorrect',
        'correction_text' => 'It was actually spider mites.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.verdict', 'incorrect')
        ->assertJsonPath('data.correction_text', 'It was actually spider mites.');

    $feedback = DiseaseFeedback::withoutGlobalScopes()->sole();
    expect($feedback->disease_detection_id)->toBe($detection->id)
        ->and($feedback->user_id)->toBe($this->user->id)
        ->and($feedback->tenant_id)->toBe($this->tenant->id);
});

it('updates the verdict in place on re-submission', function () {
    $detection = DiseaseDetection::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->postJson("/api/v1/disease/{$detection->id}/feedback", ['verdict' => 'correct'])
        ->assertCreated();
    $this->postJson("/api/v1/disease/{$detection->id}/feedback", ['verdict' => 'incorrect'])
        ->assertOk();

    expect(DiseaseFeedback::withoutGlobalScopes()->count())->toBe(1);
    expect(DiseaseFeedback::withoutGlobalScopes()->sole()->verdict)->toBe('incorrect');
});

it('rejects an invalid verdict', function () {
    $detection = DiseaseDetection::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->postJson("/api/v1/disease/{$detection->id}/feedback", ['verdict' => 'maybe'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['verdict']);
});

it('404s on a scan belonging to another tenant', function () {
    $other = Tenant::factory()->create();
    $detection = DiseaseDetection::factory()->create(['tenant_id' => $other->id]);

    $this->postJson("/api/v1/disease/{$detection->id}/feedback", ['verdict' => 'correct'])
        ->assertNotFound();
});
