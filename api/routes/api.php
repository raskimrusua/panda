<?php

use App\Http\Controllers\Api\V1\Activities\SeasonActivityController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Costs\CostEntryController;
use App\Http\Controllers\Api\V1\Crops\CropController;
use App\Http\Controllers\Api\V1\Harvests\HarvestLogController;
use App\Http\Controllers\Api\V1\InputListItems\InputListItemController;
use App\Http\Controllers\Api\V1\Seasons\SeasonController;
use App\Http\Controllers\Api\V1\Seasons\SeasonNestedController;
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
    Route::apiResource('crops', CropController::class)
        ->only(['index', 'show'])
        ->parameters(['crops' => 'crop:slug']);

    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::apiResource('seasons', SeasonController::class);

        // Nested read endpoints + PDF report
        Route::get('seasons/{season}/timeline', [SeasonNestedController::class, 'timeline']);
        Route::get('seasons/{season}/input-list', [SeasonNestedController::class, 'inputList']);
        Route::get('seasons/{season}/costs', [SeasonNestedController::class, 'costs']);
        Route::get('seasons/{season}/harvests', [SeasonNestedController::class, 'harvests']);
        Route::get('seasons/{season}/report', [SeasonNestedController::class, 'report']);

        // SeasonActivity: log-done is the only farmer-facing write
        Route::post('activities/{activity}/log-done', [SeasonActivityController::class, 'logDone']);

        // InputListItem: read + procurement marking
        Route::get('input-list-items/{inputListItem}', [InputListItemController::class, 'show']);
        Route::post('input-list-items/{inputListItem}/mark-procured', [InputListItemController::class, 'markProcured']);

        // Costs + Harvests are full apiResources
        Route::apiResource('costs', CostEntryController::class)->parameters(['costs' => 'costEntry']);
        Route::apiResource('harvests', HarvestLogController::class)->parameters(['harvests' => 'harvestLog']);
    });
});
