<?php

use App\Models\DiseaseDetection;
use App\Models\DiseaseFeedback;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['name' => 'Demo Farm']);
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $detection = fn (string $diagnosis, string $image): DiseaseDetection => DiseaseDetection::factory()->create([
        'tenant_id' => $this->tenant->id,
        'top_diagnosis' => $diagnosis,
        'image_path' => $image,
    ]);

    $this->incorrect = DiseaseFeedback::factory()->incorrect()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'disease_detection_id' => $detection('Early Blight', 'tenants/a/disease/1.jpg')->id,
    ]);
    $this->correct = DiseaseFeedback::factory()->create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $this->user->id,
        'disease_detection_id' => $detection('Late Blight', 'tenants/a/disease/2.jpg')->id,
    ]);
});

it('exports incorrect verdicts by default with the retrain columns', function () {
    $this->artisan('panda:export-feedback')->assertSuccessful();

    $files = glob(storage_path('app/exports/disease-feedback-*.csv'));
    expect($files)->toHaveCount(1);

    $lines = file((string) $files[0]);
    expect(count($lines))->toBe(2); // header + 1 incorrect row

    $header = str_getcsv($lines[0]);
    expect($header)->toContain('image_path', 'correction_text', 'top_diagnosis', 'verdict');

    $row = str_getcsv($lines[1]);
    expect($row[0])->toBe($this->incorrect->id)
        ->and($row[1])->toBe('incorrect')
        ->and($row[3])->toBe('It was actually spider mites.')
        ->and($row[5])->toBe('Early Blight')
        ->and($row[8])->toBe('tenants/a/disease/1.jpg');

    Storage::disk('local')->delete('exports/'.basename((string) $files[0]));
});

it('includes correct verdicts with --all', function () {
    $this->artisan('panda:export-feedback', ['--all' => true])->assertSuccessful();

    $files = glob(storage_path('app/exports/disease-feedback-*.csv'));
    expect(count(file((string) $files[0])))->toBe(3); // header + 2 rows

    Storage::disk('local')->delete('exports/'.basename((string) $files[0]));
});

it('filters by verdict explicitly', function () {
    $this->artisan('panda:export-feedback', ['--verdict' => 'correct'])->assertSuccessful();

    $files = glob(storage_path('app/exports/disease-feedback-*.csv'));
    $row = str_getcsv(file((string) $files[0])[1]);
    expect($row[1])->toBe('correct');

    Storage::disk('local')->delete('exports/'.basename((string) $files[0]));
});

it('rejects an invalid verdict option', function () {
    $this->artisan('panda:export-feedback', ['--verdict' => 'maybe'])->assertFailed();
});

it('warns and exits cleanly when nothing matches', function () {
    $this->artisan('panda:export-feedback', ['--since' => now()->addDay()->toIso8601String()]);

    expect(glob(storage_path('app/exports/disease-feedback-*.csv')))->toBe([]);
});
