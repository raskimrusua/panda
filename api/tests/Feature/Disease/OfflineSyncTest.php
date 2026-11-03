<?php

use App\Models\Crop;
use App\Models\DiseaseDetection;
use App\Models\Season;
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

    $this->postJson('/api/v1/disease/offline-sync', ['top_diagnosis' => 'early_blight'])
        ->assertUnauthorized();
});

it('creates an offline tree scan row with the honest confidence ceiling', function () {
    $crop = Crop::factory()->tomato()->create();

    $response = $this->postJson('/api/v1/disease/offline-sync', [
        'crop_id' => $crop->id,
        'top_diagnosis' => 'early_blight',
        'answers' => [
            'where' => 'Older leaves, brown rings',
        ],
        'client_id' => 'device-abc-001',
    ])->assertCreated();

    expect($response->json('data.provider'))->toBe('offline_decision_tree')
        ->and($response->json('data.top_diagnosis'))->toBe('Early Blight')
        ->and((float) $response->json('data.confidence'))->toBe(0.5)
        ->and($response->json('data.image_url'))->toBeNull()
        ->and($response->json('data.disease_card.name_en'))->toBe('Early Blight');

    $detection = DiseaseDetection::withoutGlobalScopes()->sole();
    expect($detection->image_path)->toBeNull()
        ->and($detection->client_id)->toBe('device-abc-001')
        ->and($detection->engine_response['offline'])->toBeTrue()
        ->and($detection->engine_response['diagnosis_key'])->toBe('early_blight')
        ->and($detection->treatments)->not->toBeEmpty();
});

it('dedupes re-sends on the client_id idempotency key', function () {
    $crop = Crop::factory()->tomato()->create();
    $payload = [
        'crop_id' => $crop->id,
        'top_diagnosis' => 'early_blight',
        'client_id' => 'device-abc-002',
    ];

    $this->postJson('/api/v1/disease/offline-sync', $payload)->assertCreated();
    $this->postJson('/api/v1/disease/offline-sync', $payload)->assertOk();

    expect(DiseaseDetection::withoutGlobalScopes()->count())->toBe(1);
});

it('keeps unknown diagnoses with the raw key as the name', function () {
    $this->postJson('/api/v1/disease/offline-sync', [
        'top_diagnosis' => 'mystery_mold',
    ])->assertCreated()
        ->assertJsonPath('data.top_diagnosis', 'mystery_mold')
        ->assertJsonPath('data.disease_card', null);
});

it('requires a diagnosis', function () {
    $this->postJson('/api/v1/disease/offline-sync', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['top_diagnosis']);
});

it('rejects a season that belongs to another tenant', function () {
    $other = Tenant::factory()->create();
    $foreignSeason = Season::factory()->create(['tenant_id' => $other->id]);

    $this->postJson('/api/v1/disease/offline-sync', [
        'top_diagnosis' => 'early_blight',
        'season_id' => $foreignSeason->id,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['season_id']);

    expect(DiseaseDetection::withoutGlobalScopes()->count())->toBe(0);
});
