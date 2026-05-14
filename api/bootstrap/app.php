<?php

use App\Http\Middleware\SetTenantFromUser;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Sentry\Laravel\Integration as SentryIntegration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'tenant' => SetTenantFromUser::class,
        ]);

        // SetTenantFromUser MUST run BEFORE SubstituteBindings so the global
        // tenant scope is active when route-model-binding queries fire.
        // Otherwise foreign-tenant URLs leak as 200 instead of 404.
        $middleware->priority([
            HandlePrecognitiveRequests::class,
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            ConvertEmptyStringsToNull::class,
            TrimStrings::class,
            Authenticate::class,
            AuthenticateWithBasicAuth::class,
            SetTenantFromUser::class,
            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,
            SubstituteBindings::class,
            AuthenticatesRequests::class,
            Authorize::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // API-only app: never try to render the HTML 'login' redirect for
        // unauthenticated requests. Default Laravel 11 behaviour calls
        // route('login') from Authenticate::redirectTo() which 500s here
        // because no such named route exists. Force JSON 401 instead for
        // anything that came in via /api/* or that explicitly accepts JSON.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Sentry: only forwards if SENTRY_LARAVEL_DSN is set in .env (no-op
        // in local dev / CI without DSN). Won't double-report — Laravel's
        // default reporter handles the local log; Sentry handles the wire.
        SentryIntegration::handles($exceptions);
    })->create();
