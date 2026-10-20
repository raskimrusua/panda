<?php

use App\Models\DiseaseDetection;
use App\Services\Crops\Disease\CropHealthClient;
use App\Services\Crops\Disease\KindwiseCropHealthClient;
use App\Services\Crops\Disease\MockCropHealthClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    config([
        'services.kindwise.key' => 'test-api-key',
        'services.kindwise.url' => 'https://crop.kindwise.com/api/v1',
    ]);
    Storage::fake(config('filesystems.default'));
    Storage::disk(config('filesystems.default'))->put('disease/leaf.jpg', 'fake-image-bytes');
});

it('binds the mock provider by default', function () {
    config(['services.crop_health.provider' => 'mock']);

    $bound = app(CropHealthClient::class);

    expect($bound)->toBeInstanceOf(MockCropHealthClient::class);
});

it('binds the Kindwise provider when CROP_HEALTH_PROVIDER=kindwise', function () {
    config(['services.crop_health.provider' => 'kindwise']);

    $bound = app(CropHealthClient::class);

    expect($bound)->toBeInstanceOf(KindwiseCropHealthClient::class);
});

it('hits the Kindwise endpoint and maps the top disease suggestion', function () {
    Http::fake([
        'crop.kindwise.com/*' => Http::response([
            'result' => [
                'disease' => [
                    'suggestions' => [
                        [
                            'name' => 'Early Blight',
                            'probability' => 0.91,
                            'details' => [
                                'treatment' => [
                                    'chemical' => ['Mancozeb', 'Chlorothalonil'],
                                    'biological' => ['Bacillus subtilis'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = app(KindwiseCropHealthClient::class)->diagnose('disease/leaf.jpg', 'tomato');

    expect($result->provider)->toBe(DiseaseDetection::PROVIDER_CROP_HEALTH)
        ->and($result->topDiagnosis)->toBe('Early Blight')
        ->and($result->confidence)->toBe(0.91);

    expect($result->treatments)->toHaveCount(3);
    expect($result->treatments[0])->toMatchArray([
        'generic' => 'Mancozeb',
        'pcpb' => null,
    ]);

    Http::assertSent(function ($request) {
        expect($request->header('Api-Key')[0] ?? null)->toBe('test-api-key');
        $body = $request->data();

        return isset($body['images'][0])
            && $body['crop'] === 'tomato'
            && $body['classification_level'] === 'species';
    });
});

it('returns a low-confidence "no disease" result when suggestions are empty', function () {
    Http::fake([
        'crop.kindwise.com/*' => Http::response([
            'result' => ['disease' => ['suggestions' => []]],
        ], 200),
    ]);

    $result = app(KindwiseCropHealthClient::class)->diagnose('disease/leaf.jpg');

    expect($result->topDiagnosis)->toBe('No disease detected')
        ->and($result->confidence)->toBeLessThan(0.7);
});

it('throws when the Kindwise API key is missing', function () {
    config(['services.kindwise.key' => '']);

    app(KindwiseCropHealthClient::class)->diagnose('disease/leaf.jpg');
})->throws(RuntimeException::class, 'Kindwise API key is not configured');

it('throws when the Kindwise API returns a non-2xx response', function () {
    Http::fake([
        'crop.kindwise.com/*' => Http::response(['error' => 'invalid api key'], 401),
    ]);

    app(KindwiseCropHealthClient::class)->diagnose('disease/leaf.jpg');
})->throws(RuntimeException::class, 'Crop.health API call failed');

it('throws when the image is missing from storage', function () {
    Storage::disk(config('filesystems.default'))->delete('disease/leaf.jpg');

    app(KindwiseCropHealthClient::class)->diagnose('disease/leaf.jpg');
})->throws(RuntimeException::class, 'Could not read image');
