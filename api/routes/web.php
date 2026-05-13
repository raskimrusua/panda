<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Sentry smoke-test — fires an unhandled RuntimeException so we can verify
 * the wire is live in production (with SENTRY_LARAVEL_DSN set). Gated to
 * superusers only so a curious farmer can't make the alert tree noisy.
 */
Route::get('/sentry-smoke-test', function () {
    abort_unless(auth()->user()?->is_superuser === true, 403);
    throw new RuntimeException('Sentry smoke test from Panda — '.now()->toIso8601String());
})->middleware('auth')->name('sentry.smoke');
