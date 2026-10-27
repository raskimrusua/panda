<?php

use App\Services\Crops\Disease\TreatmentCatalogue;

it('returns curated treatments for a known diagnosis, case-insensitive', function () {
    $treatments = TreatmentCatalogue::suggest('early blight', 'tomato');

    expect($treatments)->toHaveCount(2)
        ->and($treatments[0]['generic'])->toBe('Mancozeb 80WP')
        ->and($treatments[0]['pcpb'])->toBe('Ridomil Gold MZ 68 WG');
});

it('returns generic diseases without a crop hint', function () {
    $treatments = TreatmentCatalogue::suggest('Powdery Mildew');

    expect($treatments)->toHaveCount(1)
        ->and($treatments[0]['generic'])->toBe('Sulphur');
});

it('returns an empty list for unknown diagnoses', function () {
    expect(TreatmentCatalogue::suggest('Southern Bacterial Wilt', 'tomato'))->toBe([]);
});

it('tomato catalogue includes tomato-specific diseases', function () {
    $names = array_map(fn (array $d) => $d['name'], TreatmentCatalogue::catalogue('tomato'));

    expect($names)->toContain('Early Blight', 'Late Blight', 'Powdery Mildew');
});
