<?php

use App\Services\Crops\Disease\DiseaseCardResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('resolves a crop-scoped diagnosis by disease key', function () {
    $card = app(DiseaseCardResolver::class)->resolve('early_blight', 'tomato');

    expect($card)->not->toBeNull()
        ->and($card['name_en'])->toBe('Early Blight')
        ->and($card['name_sw'])->not->toBeEmpty()
        ->and($card['severity_classes'])->not->toBeEmpty();
});

it('resolves case-insensitively by english name', function () {
    $card = app(DiseaseCardResolver::class)->resolve('EARLY blight', 'tomato');

    expect($card['name_en'])->toBe('Early Blight');
});

it('resolves a crop-qualified provider name via relaxed suffix matching', function () {
    $card = app(DiseaseCardResolver::class)->resolve('Tomato early blight', 'tomato');

    expect($card['key'])->toBe('early_blight');
});

it('matches key-style names against display names', function () {
    $card = app(DiseaseCardResolver::class)->resolve('Aphid Infestation');

    expect($card)->not->toBeNull()
        ->and($card['key'])->toBe('aphids');
});

it('falls back to a global search when the crop hint misses', function () {
    $card = app(DiseaseCardResolver::class)->resolve('Powdery Mildew', 'tomato');

    expect($card)->not->toBeNull()
        ->and($card['key'])->toBe('powdery_mildew');
});

it('returns null when no card matches', function () {
    $card = app(DiseaseCardResolver::class)->resolve('Cosmic space blight', 'tomato');

    expect($card)->toBeNull();
});
