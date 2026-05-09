<?php

use App\Http\Controllers\Api\V1\Crops\CropController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
| API v1 routes — Panda backend
|
| Public catalogue endpoints (Crop, Disease library, Dealer directory) require
| no auth and are cached at the edge by CF in front of api.panda.shira.farm.
|
| Tenant-scoped endpoints (Season, HarvestLog, etc.) live behind auth:sanctum
| with the tenancy middleware (added in PR #3).
*/

Route::prefix('v1')->group(function () {
    // Public — shared catalogue
    Route::apiResource('crops', CropController::class)
        ->only(['index', 'show'])
        ->parameters(['crops' => 'crop:slug']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
