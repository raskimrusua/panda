<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Sentry\Laravel\Integration as SentryIntegration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Sentry: only forwards if SENTRY_LARAVEL_DSN is set in .env (no-op
        // in local dev / CI without DSN). Won't double-report — Laravel's
        // default reporter handles the local log; Sentry handles the wire.
        SentryIntegration::handles($exceptions);
    })->create();
