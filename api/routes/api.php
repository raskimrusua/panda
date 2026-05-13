<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Crops\CropController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\Seasons\SeasonController;
use Illuminate\Support\Facades\Route;

/*
| API v1 routes — Panda backend
|
| Public catalogue endpoints (Crop, Disease library, Dealer directory) require
| no auth and are cached at the edge by CF in front of api.panda.shira.farm.
|
| Tenant-scoped endpoints live behind `auth:sanctum` + the `tenant` middleware
| (SetTenantFromUser) which sets the current Spatie tenant from the user FK.
*/

Route::prefix('v1')->group(function () {
    Route::get('health', HealthController::class);

    // Public — shared catalogue
    Route::apiResource('crops', CropController::class)
        ->only(['index', 'show'])
        ->parameters(['crops' => 'crop:slug']);

    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::apiResource('seasons', SeasonController::class);
    });
});
