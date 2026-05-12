<?php

namespace App\Providers;

use App\Models\HarvestLog;
use App\Models\Season;
use App\Observers\HarvestLogObserver;
use App\Observers\SeasonObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Season::observe(SeasonObserver::class);
        HarvestLog::observe(HarvestLogObserver::class);
    }
}
