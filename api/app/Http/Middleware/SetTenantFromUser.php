<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetTenantFromUser — runs after `auth:sanctum`.
 *
 * Spatie's UserTenantFinder runs in service-provider boot, before middleware,
 * so `auth()->user()` is typically null on that pass. This middleware re-runs
 * the lookup post-auth and calls `$tenant->makeCurrent()` so that the global
 * `BelongsToTenant` scope and tenant-aware queues both see the right tenant.
 *
 * If the user has no tenant (shouldn't happen post-registration; defensive),
 * 403 — without a tenant the request cannot be safely scoped.
 */
class SetTenantFromUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->tenant_id) {
            $user->tenant->makeCurrent();
        } else {
            abort(403, 'User is not associated with a tenant.');
        }

        return $next($request);
    }
}
