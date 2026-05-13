<?php

namespace App\Providers;

use App\Models\HarvestLog;
use App\Models\Season;
use App\Observers\HarvestLogObserver;
use App\Observers\SeasonObserver;
use App\Services\Crops\Disease\CropHealthClient;
use App\Services\Crops\Disease\MockCropHealthClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // P1-P4 ships with the mock. P5 swap: bind to KindwiseCropHealthClient
        // when CROP_HEALTH_PROVIDER=crop_health AND a real DSN is set.
        $this->app->bind(CropHealthClient::class, MockCropHealthClient::class);
    }

    public function boot(): void
    {
        Season::observe(SeasonObserver::class);
        HarvestLog::observe(HarvestLogObserver::class);
    }
}
