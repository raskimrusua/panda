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
    // Fake the default disk (whatever STORAGE_BACKEND is set to). The
    // production code goes through Storage::disk(config('filesystems.default'))
    // via ImageUploadService so the test never names a specific disk.
    Storage::fake(config('filesystems.default'));
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
        ->assertJsonStructure(['data' => ['id', 'image_url', 'provider', 'top_diagnosis', 'confidence', 'treatments', 'disclaimer']]);

    expect($response->json('data.provider'))->toBe('mock')
        ->and($response->json('data.top_diagnosis'))->not->toBeNull()
        ->and((float) $response->json('data.confidence'))->toBeBetween(0.7, 1.0)
        // Advisory disclaimer must ride in the payload so offline-cached or
        // programmatic API consumers cannot get a diagnosis without it.
        ->and($response->json('data.disclaimer'))->toBe((string) config('legal.disease_disclaimer'));

    // The DB row stores the relative path; the API response exposes a
    // signed URL via ImageUploadService::temporaryUrl().
    $detection = DiseaseDetection::withoutGlobalScopes()->sole();
    expect($detection->tenant_id)->toBe($this->tenant->id)
        ->and($detection->image_path)
        ->toStartWith("tenants/{$this->tenant->id}/disease/{$detection->id}.");

    Storage::disk(config('filesystems.default'))->assertExists($detection->image_path);
    // image_url in the response is derived (signed/temporary) — it should
    // exist and reference the underlying path.
    expect($response->json('data.image_url'))->not->toBeNull()
        ->and($response->json('data.image_url'))->toContain($detection->image_path);
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
