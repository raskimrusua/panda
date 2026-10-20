<?php

namespace App\Providers;

use App\Models\HarvestLog;
use App\Models\Season;
use App\Observers\HarvestLogObserver;
use App\Observers\SeasonObserver;
use App\Services\Crops\Disease\CropHealthClient;
use App\Services\Crops\Disease\KindwiseCropHealthClient;
use App\Services\Crops\Disease\MockCropHealthClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Disease-detection provider is env-driven. `kindwise` swaps in
        // the real Crop.health API; anything else (default `mock`) keeps
        // the deterministic offline mock — zero cost, no network. See
        // config/services.php `crop_health.provider`.
        $this->app->bind(CropHealthClient::class, function () {
            return match (config('services.crop_health.provider')) {
                'kindwise' => $this->app->make(KindwiseCropHealthClient::class),
                default => $this->app->make(MockCropHealthClient::class),
            };
        });
    }

    public function boot(): void
    {
        Season::observe(SeasonObserver::class);
        HarvestLog::observe(HarvestLogObserver::class);
    }
}
