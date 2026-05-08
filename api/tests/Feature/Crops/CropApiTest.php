<?php

use App\Models\Crop;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('GET /api/v1/crops returns paginated catalogue', function () {
    Crop::factory()->tomato()->create();
    Crop::factory()->kale()->create();
    Crop::factory()->cabbage()->create();

    $response = $this->getJson('/api/v1/crops')->assertOk();

    expect($response->json('data'))->toHaveCount(3)
        ->and($response->json('meta.total'))->toBe(3)
        ->and($response->json('meta.current_page'))->toBe(1);
});

it('GET /api/v1/crops returns expected resource shape', function () {
    Crop::factory()->tomato()->create();

    $this->getJson('/api/v1/crops')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                ['id', 'slug', 'name_en', 'name_sw', 'category', 'harvest_type', 'image_url', 'jica_manual_ref', 'phase_added', 'is_active'],
            ],
            'meta' => ['current_page', 'last_page', 'total'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});

it('does not leak deleted_at or system fields', function () {
    Crop::factory()->tomato()->create();

    $response = $this->getJson('/api/v1/crops')->json('data.0');

    expect($response)->not->toHaveKey('deleted_at')
        ->and($response)->not->toHaveKey('created_at')
        ->and($response)->not->toHaveKey('updated_at');
});

it('GET /api/v1/crops/{slug} returns the crop', function () {
    Crop::factory()->tomato()->create();

    $this->getJson('/api/v1/crops/tomato')
        ->assertOk()
        ->assertJsonPath('data.slug', 'tomato')
        ->assertJsonPath('data.name_en', 'Tomato')
        ->assertJsonPath('data.name_sw', 'Nyanya');
});

it('GET /api/v1/crops/{unknown-slug} returns 404', function () {
    $this->getJson('/api/v1/crops/nonexistent')->assertNotFound();
});

it('filters by category', function () {
    Crop::factory()->tomato()->create();   // vegetable
    Crop::factory()->kale()->create();     // leafy_green

    $vegetables = $this->getJson('/api/v1/crops?category=vegetable')->assertOk();
    $leafy = $this->getJson('/api/v1/crops?category=leafy_green')->assertOk();

    expect($vegetables->json('data'))->toHaveCount(1)
        ->and($vegetables->json('data.0.slug'))->toBe('tomato')
        ->and($leafy->json('data'))->toHaveCount(1)
        ->and($leafy->json('data.0.slug'))->toBe('kale');
});

it('filters by harvest_type', function () {
    Crop::factory()->tomato()->create();    // multi
    Crop::factory()->cabbage()->create();   // single

    $multi = $this->getJson('/api/v1/crops?harvest_type=multi')->assertOk();
    $single = $this->getJson('/api/v1/crops?harvest_type=single')->assertOk();

    expect($multi->json('data'))->toHaveCount(1)
        ->and($multi->json('data.0.slug'))->toBe('tomato')
        ->and($single->json('data'))->toHaveCount(1)
        ->and($single->json('data.0.slug'))->toBe('cabbage');
});

it('full-text search across name_en, name_sw, slug', function () {
    Crop::factory()->tomato()->create();    // Tomato / Nyanya
    Crop::factory()->kale()->create();      // Kale / Sukuma Wiki

    $byEnglish = $this->getJson('/api/v1/crops?q=Toma')->assertOk();
    $bySwahili = $this->getJson('/api/v1/crops?q=Sukuma')->assertOk();
    $byMiss = $this->getJson('/api/v1/crops?q=avocado')->assertOk();

    expect($byEnglish->json('data'))->toHaveCount(1)
        ->and($byEnglish->json('data.0.slug'))->toBe('tomato')
        ->and($bySwahili->json('data'))->toHaveCount(1)
        ->and($bySwahili->json('data.0.slug'))->toBe('kale')
        ->and($byMiss->json('data'))->toHaveCount(0);
});

it('hides inactive crops by default', function () {
    Crop::factory()->tomato()->create();
    Crop::factory()->kale()->inactive()->create();

    $response = $this->getJson('/api/v1/crops')->assertOk();

    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.slug'))->toBe('tomato');
});

it('shows phase-1 crops by default', function () {
    Crop::factory()->tomato()->create();           // phase 1
    Crop::factory()->cabbage()->phase2()->create(); // phase 2

    $defaults = $this->getJson('/api/v1/crops')->assertOk();
    $phase2 = $this->getJson('/api/v1/crops?phase=2')->assertOk();

    expect($defaults->json('data'))->toHaveCount(1)  // phase 1 only
        ->and($defaults->json('data.0.slug'))->toBe('tomato')
        ->and($phase2->json('data'))->toHaveCount(2); // both
});

it('rejects invalid category filter with 422', function () {
    $this->getJson('/api/v1/crops?category=invalid')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['category']);
});

it('rejects invalid harvest_type filter with 422', function () {
    $this->getJson('/api/v1/crops?harvest_type=triple')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['harvest_type']);
});

it('does not N+1 on list endpoint', function () {
    Crop::factory()->count(20)->create();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $this->getJson('/api/v1/crops?per_page=20')->assertOk();
    $queryCount = count(DB::getQueryLog());

    // Tolerance: count + select. Crop has no relations to eager-load.
    expect($queryCount)->toBeLessThan(5);
});

it('respects per_page param within bounds', function () {
    Crop::factory()->count(15)->create();

    $small = $this->getJson('/api/v1/crops?per_page=5')->assertOk();
    expect($small->json('data'))->toHaveCount(5);

    $rejected = $this->getJson('/api/v1/crops?per_page=999')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['per_page']);
});
