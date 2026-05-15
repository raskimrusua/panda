<?php

use App\Http\Controllers\Api\V1\Activities\SeasonActivityController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Costs\CostEntryController;
use App\Http\Controllers\Api\V1\Crops\CropController;
use App\Http\Controllers\Api\V1\Dealers\DealerController;
use App\Http\Controllers\Api\V1\Disease\DiseaseDetectionController;
use App\Http\Controllers\Api\V1\Harvests\HarvestLogController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\InputListItems\InputListItemController;
use App\Http\Controllers\Api\V1\Prices\MarketPriceController;
use App\Http\Controllers\Api\V1\Seasons\SeasonController;
use App\Http\Controllers\Api\V1\Seasons\SeasonNestedController;
use Illuminate\Support\Facades\Route;

/*
| API v1 routes — Panda backend
|
| Public catalogue endpoints (Crop, Market price) require no auth and are
| cached at the edge by CF in front of api.panda.shira.farm.
|
| Auth-required-but-not-tenant-scoped: Dealer (shared catalogue every farm
| sees the same dealers).
|
| Tenant-scoped endpoints live behind `auth:sanctum` + the `tenant` middleware
| (SetTenantFromUser) which sets the current Spatie tenant from the user FK.
*/

Route::prefix('v1')->group(function () {
    Route::get('health', HealthController::class);

    /* Public catalogue */
    Route::apiResource('crops', CropController::class)
        ->only(['index', 'show'])
        ->parameters(['crops' => 'crop:slug']);

    Route::get('prices/{crop:slug}/latest', [MarketPriceController::class, 'latest']);
    Route::get('prices/{crop:slug}/history', [MarketPriceController::class, 'history']);
    Route::get('prices/{crop:slug}/forecast', [MarketPriceController::class, 'forecast']);

    /* Auth */
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/password/forgot', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:6,1');
    Route::post('auth/password/reset', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:6,1');

    // Verification link signed at email-send time; signed middleware
    // validates the URL signature without requiring authentication.
    Route::get('auth/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/email/verification-notification', [AuthController::class, 'sendVerification'])
            ->middleware('throttle:6,1');

        /* Tenant-scoped resources */
        Route::apiResource('seasons', SeasonController::class);

        Route::get('seasons/{season}/timeline', [SeasonNestedController::class, 'timeline']);
        Route::get('seasons/{season}/input-list', [SeasonNestedController::class, 'inputList']);
        Route::get('seasons/{season}/costs', [SeasonNestedController::class, 'costs']);
        Route::get('seasons/{season}/harvests', [SeasonNestedController::class, 'harvests']);
        Route::get('seasons/{season}/report', [SeasonNestedController::class, 'report']);
        Route::get('seasons/{season}/costs.csv', [SeasonNestedController::class, 'costsCsv']);
        Route::get('seasons/{season}/harvests.csv', [SeasonNestedController::class, 'harvestsCsv']);
        Route::get('seasons/{season}/activities.csv', [SeasonNestedController::class, 'activitiesCsv']);

        Route::post('activities/{activity}/log-done', [SeasonActivityController::class, 'logDone']);

        Route::get('input-list-items/{inputListItem}', [InputListItemController::class, 'show']);
        Route::post('input-list-items/{inputListItem}/mark-procured', [InputListItemController::class, 'markProcured']);

        Route::apiResource('costs', CostEntryController::class)->parameters(['costs' => 'costEntry']);
        Route::apiResource('harvests', HarvestLogController::class)->parameters(['harvests' => 'harvestLog']);

        /* Disease detection — tenant-scoped (each scan belongs to a farm) */
        Route::post('disease/detect', [DiseaseDetectionController::class, 'detect']);
        Route::get('disease/history', [DiseaseDetectionController::class, 'history']);
        Route::get('disease/{diseaseDetection}', [DiseaseDetectionController::class, 'show']);

        /* Dealer directory — auth required, NOT tenant-scoped */
        Route::get('dealers', [DealerController::class, 'index']);
        Route::get('dealers/{dealer}', [DealerController::class, 'show']);
    });
});
