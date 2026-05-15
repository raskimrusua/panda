<?php

use App\Services\Storage\ImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('uploads a disease photo to the default disk under tenant-scoped path', function () {
    Storage::fake(config('filesystems.default'));
    $file = UploadedFile::fake()->image('leaf.jpg', 800, 600);

    $path = app(ImageUploadService::class)->uploadDiseasePhoto($file, 'tenant-A', 'detection-X');

    expect($path)->toBe('tenants/tenant-A/disease/detection-X.jpg');
    Storage::disk(config('filesystems.default'))->assertExists($path);
});

it('lowercases the extension and falls back to jpg when missing', function () {
    Storage::fake(config('filesystems.default'));
    $file = UploadedFile::fake()->image('LEAF.JPG');

    $path = app(ImageUploadService::class)->uploadDiseasePhoto($file, 't', 'd');

    expect($path)->toBe('tenants/t/disease/d.jpg');
});

it('temporaryUrl returns a URL that references the stored path', function () {
    Storage::fake(config('filesystems.default'));
    Storage::disk(config('filesystems.default'))->put('tenants/t/disease/d.jpg', 'fake-bytes');

    $url = app(ImageUploadService::class)->temporaryUrl('tenants/t/disease/d.jpg', 15);

    expect($url)->toContain('tenants/t/disease/d.jpg');
});

it('temporaryUrl passes through legacy full URLs untouched', function () {
    Storage::fake(config('filesystems.default'));
    $legacy = 'https://images.example.com/panda/demo/leaf-1.jpg';

    $url = app(ImageUploadService::class)->temporaryUrl($legacy, 15);

    expect($url)->toBe($legacy);
});

it('respects STORAGE_BACKEND override — write goes to the configured disk, not the original default', function () {
    // Stand up a second local disk masquerading as "s3" so we can prove the
    // service follows config('filesystems.default') and never hardcodes 'r2'.
    config(['filesystems.default' => 's3']);
    config(['filesystems.disks.s3' => [
        'driver' => 'local',
        'root' => storage_path('framework/testing/s3-override'),
    ]]);
    Storage::fake('s3');

    $path = app(ImageUploadService::class)->uploadDiseasePhoto(
        UploadedFile::fake()->image('leaf.png'),
        't',
        'd',
    );

    Storage::disk('s3')->assertExists($path);
    expect($path)->toEndWith('.png');
});
