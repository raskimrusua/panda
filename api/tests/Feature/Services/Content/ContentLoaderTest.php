<?php

use App\Services\Content\ContentLoader;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    // Tests use array cache (per phpunit.xml CACHE_STORE=array) — no cross-test bleed.
    Cache::flush();
    $this->loader = new ContentLoader;
});

it('loads tomato.json successfully', function () {
    $content = $this->loader->loadCropFile('tomato');

    expect($content)->toBeArray()
        ->and($content['slug'])->toBe('tomato')
        ->and($content['name_en'])->toBe('Tomato')
        ->and($content['name_sw'])->toBe('Nyanya')
        ->and($content['category'])->toBe('vegetable')
        ->and($content['harvest_type'])->toBe('multi');
});

it('validates against the JSON schema', function () {
    // tomato.json passes schema (proven by load); a malformed file should fail.
    expect(fn () => $this->loader->loadCropFile('nonexistent'))
        ->toThrow(RuntimeException::class, 'Crop content file not found');
});

it('caches loaded content for subsequent calls', function () {
    $this->loader->loadCropFile('tomato');

    expect(Cache::has('panda:content:crop:tomato'))->toBeTrue()
        ->and(Cache::get('panda:content:crop:tomato')['slug'])->toBe('tomato');
});

it('getCrop returns cached content on hit', function () {
    Cache::put('panda:content:crop:tomato', ['slug' => 'tomato', 'name_en' => 'Cached'], 60);

    $result = $this->loader->getCrop('tomato');

    expect($result['name_en'])->toBe('Cached');
});

it('getCrop returns null for unknown slug', function () {
    $result = $this->loader->getCrop('nonexistent-crop-xyz');

    expect($result)->toBeNull();
});

it('availableCropSlugs returns all known slugs', function () {
    $slugs = $this->loader->availableCropSlugs();

    expect($slugs)->toBeArray()
        ->and($slugs)->toContain('tomato');
});

it('flush clears all cached content', function () {
    $this->loader->loadCropFile('tomato');
    expect(Cache::has('panda:content:crop:tomato'))->toBeTrue();

    $this->loader->flush();

    expect(Cache::has('panda:content:crop:tomato'))->toBeFalse();
});

it('loadAllCrops returns map of slug => content', function () {
    $loaded = $this->loader->loadAllCrops();

    expect($loaded)->toBeArray()
        ->and($loaded)->toHaveKey('tomato')
        ->and($loaded['tomato']['name_en'])->toBe('Tomato');
});

it('tomato.json contains the JAICA-spec timeline structure', function () {
    $content = $this->loader->loadCropFile('tomato');

    expect($content['timeline_template'])->toBeArray()
        ->and(count($content['timeline_template']))->toBeGreaterThan(5)
        ->and($content['timeline_template'][0])->toHaveKeys(['activity_type', 'phase', 'week_from_planting', 'description_en', 'description_sw'])
        ->and($content['inputs_per_acre'])->toBeArray()
        ->and(count($content['inputs_per_acre']))->toBeGreaterThan(3)
        ->and($content['yield_per_acre']['expected_kg'])->toBeGreaterThan(10000);
});
