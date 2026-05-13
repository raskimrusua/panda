<?php

use App\Models\CostEntry;
use App\Models\DiseaseDetection;
use App\Models\HarvestLog;
use App\Models\Season;
use App\Models\SeasonActivity;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds 12 months — 3 seasons covering full lifecycle', function () {
    $this->artisan('panda:seed-demo', [
        '--months' => 12,
        '--no-catalog' => true,
    ])->assertExitCode(0);

    $tenant = Tenant::withoutGlobalScopes()->where('slug', 'demo-farm')->firstOrFail();
    $user = User::withoutGlobalScopes()->where('email', 'demo@panda.shira.farm')->firstOrFail();

    expect($user->is_superuser)->toBeTrue();
    expect($user->tenant_id)->toBe($tenant->id);

    $tenant->makeCurrent();
    try {
        expect(Season::count())->toBe(3);
        expect(Season::where('status', Season::STATUS_COMPLETE)->count())->toBe(1);
        expect(Season::where('status', Season::STATUS_HARVESTING)->count())->toBe(1);
        expect(Season::where('status', Season::STATUS_PLANNING)->count())->toBe(1);

        // Engine ran on every season (auto via SeasonObserver)
        expect(SeasonActivity::count())->toBeGreaterThan(0);
        expect(CostEntry::count())->toBeGreaterThan(20);
        expect(HarvestLog::count())->toBeGreaterThan(10);
        expect(DiseaseDetection::count())->toBeGreaterThan(0);

        // The harvesting season has the most done activities
        $harvestingSeason = Season::where('status', Season::STATUS_HARVESTING)->firstOrFail();
        expect(
            SeasonActivity::where('season_id', $harvestingSeason->id)
                ->where('status', SeasonActivity::STATUS_DONE)
                ->count()
        )->toBeGreaterThan(0);
    } finally {
        $tenant->forgetCurrent();
    }
});

it('seeds 6 months — 2 seasons (1 harvesting, 1 active)', function () {
    $this->artisan('panda:seed-demo', [
        '--months' => 6,
        '--no-catalog' => true,
    ])->assertExitCode(0);

    $tenant = Tenant::withoutGlobalScopes()->where('slug', 'demo-farm')->firstOrFail();
    $tenant->makeCurrent();
    try {
        expect(Season::count())->toBe(2);
        expect(HarvestLog::count())->toBeGreaterThan(0);
    } finally {
        $tenant->forgetCurrent();
    }
});

it('seeds 3 months — 1 active season, no harvests yet', function () {
    $this->artisan('panda:seed-demo', [
        '--months' => 3,
        '--no-catalog' => true,
    ])->assertExitCode(0);

    $tenant = Tenant::withoutGlobalScopes()->where('slug', 'demo-farm')->firstOrFail();
    $tenant->makeCurrent();
    try {
        expect(Season::count())->toBe(1);
        expect(Season::where('status', Season::STATUS_ACTIVE)->count())->toBe(1);
        expect(HarvestLog::count())->toBe(0); // 10 weeks elapsed; first pick is at week 12
        expect(CostEntry::count())->toBeGreaterThan(0);
    } finally {
        $tenant->forgetCurrent();
    }
});

it('rejects invalid --months values', function () {
    $this->artisan('panda:seed-demo', [
        '--months' => 7,
        '--no-catalog' => true,
    ])->assertExitCode(1);
});

it('--fresh wipes the existing demo tenant before reseeding', function () {
    $this->artisan('panda:seed-demo', ['--months' => 3, '--no-catalog' => true])->assertExitCode(0);
    $firstTenantId = Tenant::withoutGlobalScopes()->where('slug', 'demo-farm')->value('id');

    $this->artisan('panda:seed-demo', ['--months' => 6, '--no-catalog' => true, '--fresh' => true])->assertExitCode(0);
    $secondTenantId = Tenant::withoutGlobalScopes()->where('slug', 'demo-farm')->value('id');

    expect($secondTenantId)->not->toBe($firstTenantId);
    Tenant::withoutGlobalScopes()->find($secondTenantId)->makeCurrent();
    try {
        expect(Season::count())->toBe(2);
    } finally {
        Tenant::current()?->forgetCurrent();
    }
});

it('is idempotent without --fresh — re-running upgrades the same tenant', function () {
    $this->artisan('panda:seed-demo', ['--months' => 3, '--no-catalog' => true])->assertExitCode(0);
    $firstTenantId = Tenant::withoutGlobalScopes()->where('slug', 'demo-farm')->value('id');

    $this->artisan('panda:seed-demo', ['--months' => 12, '--no-catalog' => true])->assertExitCode(0);
    $secondTenantId = Tenant::withoutGlobalScopes()->where('slug', 'demo-farm')->value('id');

    expect($secondTenantId)->toBe($firstTenantId);
});
