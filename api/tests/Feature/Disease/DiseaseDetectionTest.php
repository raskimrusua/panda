<?php

use App\Models\Crop;
use App\Models\DiseaseDetection;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Crops\Disease\CropHealthClient;
use App\Services\Crops\Disease\MockCropHealthClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->actingAs($this->user);
});

it('rejects unauthenticated requests', function () {
    auth()->logout();
    $this->postJson('/api/v1/disease/detect')->assertUnauthorized();
});

it('mock client is bound by default in test environment', function () {
    $bound = app(CropHealthClient::class);
    expect($bound)->toBeInstanceOf(MockCropHealthClient::class);
});

it('saves the uploaded image and returns a diagnosis', function () {
    $crop = Crop::factory()->tomato()->create();
    $file = UploadedFile::fake()->image('leaf.jpg', 800, 600);

    $response = $this->postJson('/api/v1/disease/detect', [
        'image' => $file,
        'crop_id' => $crop->id,
    ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'image_url', 'provider', 'top_diagnosis', 'confidence', 'treatments']]);

    expect($response->json('data.provider'))->toBe('mock')
        ->and($response->json('data.top_diagnosis'))->not->toBeNull()
        ->and((float) $response->json('data.confidence'))->toBeBetween(0.7, 1.0);

    Storage::disk('public')->assertExists(
        str_replace(Storage::disk('public')->url(''), '', $response->json('data.image_url'))
    );

    expect(DiseaseDetection::withoutGlobalScopes()->count())->toBe(1)
        ->and(DiseaseDetection::withoutGlobalScopes()->first()->tenant_id)->toBe($this->tenant->id);
});

it('rejects non-image upload', function () {
    $file = UploadedFile::fake()->create('document.pdf', 100);

    $this->postJson('/api/v1/disease/detect', ['image' => $file])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('rejects oversized image', function () {
    $file = UploadedFile::fake()->image('huge.jpg')->size(6000); // > 5 MB

    $this->postJson('/api/v1/disease/detect', ['image' => $file])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);
});

it('mock diagnosis is deterministic for the same image path + crop slug', function () {
    $client = new MockCropHealthClient;

    $a = $client->diagnose('disease-scans/sample.jpg', 'tomato');
    $b = $client->diagnose('disease-scans/sample.jpg', 'tomato');

    expect($a->topDiagnosis)->toBe($b->topDiagnosis)
        ->and($a->confidence)->toBe($b->confidence)
        ->and($a->treatments)->toBe($b->treatments);
});

it('mock returns tomato-specific diseases when crop_slug=tomato', function () {
    $client = new MockCropHealthClient;
    $tomatoDiseases = ['Early Blight', 'Late Blight', 'Tomato Yellow Leaf Curl Virus (TYLCV)', 'Powdery Mildew', 'Aphid Infestation'];

    // Several attempts to collect a tomato-specific disease.
    $hits = 0;
    for ($i = 0; $i < 20; $i++) {
        $result = $client->diagnose("scan-$i.jpg", 'tomato');
        if (in_array($result->topDiagnosis, ['Early Blight', 'Late Blight', 'Tomato Yellow Leaf Curl Virus (TYLCV)'], true)) {
            $hits++;
        }
        expect(in_array($result->topDiagnosis, $tomatoDiseases, true))->toBeTrue();
    }
    // Statistical: with tomato hint, tomato-specific diseases should appear at least once across 20 trials.
    expect($hits)->toBeGreaterThan(0);
});

it('history lists own-tenant scans only', function () {
    DiseaseDetection::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);

    $other = Tenant::factory()->create();
    DiseaseDetection::factory()->count(2)->create(['tenant_id' => $other->id]);

    $this->getJson('/api/v1/disease/history')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('CROSS-TENANT: cannot read another tenant scan (404)', function () {
    $other = Tenant::factory()->create();
    $foreign = DiseaseDetection::factory()->create(['tenant_id' => $other->id]);

    $this->getJson("/api/v1/disease/{$foreign->id}")
        ->assertNotFound();
});
