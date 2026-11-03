<?php

use App\Models\Crop;
use App\Models\DiseaseDetection;
use App\Models\Season;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Content\ContentLoader;
use App\Services\Crops\Disease\TreatmentCatalogue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Crop-content sampling pipeline test (S1 smoke, 2026-11).
 *
 * Samples 4 crops across the catalogue — tomato (fruit veg), kale (leafy),
 * maize (staple), pawpaw (perennial fruit) — and exercises the three layers
 * the user asked about: disease API, photo capture, result suggestion.
 *
 * Layer 1 (data): enriched crop JSONs serve card-shaped bilingual disease
 * data + https links.
 * Layer 2 (pipeline): POST disease/detect accepts a photo per crop and
 * persists a tenant-scoped detection.
 * Layer 3 (suggestion): plan-treatment consumes the stored treatments; a
 * coverage probe documents how many of each crop's own diseases exist in
 * the TreatmentCatalogue (tomato covered; others currently generic-only —
 * the known gap this test locks in so catalogue extensions are deliberate).
 */
const SAMPLE_CROPS = ['tomato', 'kale', 'maize', 'pawpaw'];
const ENRICHED_CROPS = ['kale', 'maize', 'pawpaw'];

/**
 * @param  list<string|array<string, mixed>>  $diseases
 * @return list<string>
 */
function diseaseNames(array $diseases): array
{
    return array_map(
        fn ($d) => is_string($d) ? $d : ($d['name_en'] ?? ''),
        $diseases
    );
}

beforeEach(function () {
    Cache::flush();
    Storage::fake(config('filesystems.default'));
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->actingAs($this->user);
    $this->loader = new ContentLoader;
});

it('enriched crops load card-shaped bilingual disease data', function (string $slug) {
    $content = $this->loader->loadCropFile($slug);

    expect($content['diseases'])->toBeArray()->not->toBeEmpty();

    foreach ($content['diseases'] as $disease) {
        expect($disease)->toBeArray();
        expect(array_keys($disease))->toContain(
            'key', 'name_en', 'name_sw', 'type', 'severity_classes',
            'symptoms_en', 'symptoms_sw', 'treatment_en', 'treatment_sw',
            'prevention_en', 'prevention_sw', 'scanner_notes'
        );
        expect($disease['name_en'])->not->toBeEmpty()
            ->and($disease['name_sw'])->not->toBeEmpty()
            ->and($disease['symptoms_en'])->not->toBeEmpty()
            ->and($disease['symptoms_sw'])->not->toBeEmpty()
            ->and($disease['treatment_en'])->not->toBeEmpty()
            ->and($disease['treatment_sw'])->not->toBeEmpty()
            ->and($disease['scanner_notes'])->not->toBeEmpty();
    }
})->with(ENRICHED_CROPS);

it('every sample crop has diseases listed', function (string $slug) {
    $content = $this->loader->loadCropFile($slug);
    expect(diseaseNames($content['diseases']))->not->toBeEmpty();
})->with(SAMPLE_CROPS);

it('enriched crops carry https links including a blog guide', function (string $slug) {
    $content = $this->loader->loadCropFile($slug);

    expect($content['links'])->toBeArray()->not->toBeEmpty();
    foreach ($content['links'] as $link) {
        expect($link['url'])->toStartWith('https://');
    }
    expect(array_column($content['links'], 'kind'))->toContain('blog_guide');
})->with(ENRICHED_CROPS);

it('photo capture + detect + plan-treatment pipeline works for each sample crop', function (string $slug) {
    $crop = Crop::factory()->create(['slug' => $slug]);
    $file = UploadedFile::fake()->image("{$slug}-leaf.jpg", 800, 600);

    $response = $this->postJson('/api/v1/disease/detect', [
        'image' => $file,
        'crop_id' => $crop->id,
    ])
        ->assertCreated()
        ->assertJsonStructure(['data' => [
            'id', 'image_url', 'provider', 'top_diagnosis', 'confidence', 'treatments', 'disclaimer',
        ]]);

    expect($response->json('data.provider'))->toBe('mock')
        ->and($response->json('data.top_diagnosis'))->not->toBeNull()
        ->and((float) $response->json('data.confidence'))->toBeBetween(0.7, 1.0);

    $detection = DiseaseDetection::withoutGlobalScopes()->sole();
    expect($detection->tenant_id)->toBe($this->tenant->id)
        ->and($detection->crop_id)->toBe($crop->id)
        ->and($detection->treatments)->toBeArray()->not->toBeEmpty();

    Storage::disk(config('filesystems.default'))->assertExists($detection->image_path);

    // Result suggestion: turn the stored diagnosis into a logged treatment.
    $season = Season::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->postJson("/api/v1/disease/{$detection->id}/plan-treatment", [
        'season_id' => $season->id,
        'treatment_index' => 0,
        'amount_kes' => 400,
    ])->assertCreated();
})->with(SAMPLE_CROPS);

it('documents result-suggestion coverage per sample crop', function () {
    $report = [];
    foreach (SAMPLE_CROPS as $slug) {
        $content = $this->loader->loadCropFile($slug);
        $jsonKeys = array_map(
            'mb_strtolower',
            array_map(fn ($d) => is_string($d) ? $d : ($d['key'] ?? ''), $content['diseases'])
        );
        $jsonNames = array_map('mb_strtolower', diseaseNames($content['diseases']));
        $catalogueNames = array_map(
            'mb_strtolower',
            array_column(TreatmentCatalogue::catalogue($slug), 'name')
        );
        $report[$slug] = [
            'json_diseases' => count($jsonNames),
            'by_key' => count(array_intersect($jsonKeys, $catalogueNames)),
            'by_name' => count(array_intersect($jsonNames, $catalogueNames)),
        ];
    }

    // Post-bridge state (TreatmentCatalogue extended per crop, S1 2026-11):
    // the crop JSON disease cards and the catalogue now reference each other
    // by key. The numbers below are the real intersections — they change only
    // when the catalogue or the JSONs deliberately change. Tomato intersects
    // by key only (its JSON holds string keys, the catalogue holds Kindwise
    // display names) — the tomato name-space gap remains a known follow-up.
    expect($report['tomato']['by_key'])->toBe(6);
    expect($report['kale']['by_key'])->toBe(5);
    expect($report['maize']['by_key'])->toBe(5);
    expect($report['pawpaw']['by_key'])->toBe(5);

    expect($report['tomato']['by_name'])->toBe(0);
    expect($report['kale']['by_name'])->toBe(5);
    expect($report['maize']['by_name'])->toBe(5);
    expect($report['pawpaw']['by_name'])->toBe(5);
});
