<?php

namespace App\Multitenancy;

use App\Http\Middleware\SetTenantFromUser;
use Illuminate\Http\Request;
use Spatie\Multitenancy\Contracts\IsTenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;

/**
 * Resolves the current tenant from the authenticated user.
 *
 * Replaces Spatie's default DomainTenantFinder (which assumes per-tenant
 * subdomains). Panda is single-DB row-based — the User model carries a
 * `tenant_id` FK and that is the source of truth.
 *
 * Spatie calls this once per request from MultitenancyServiceProvider's
 * boot phase, BEFORE middleware runs. At that point the Sanctum guard may
 * not have hydrated `auth()->user()` yet, so this finder commonly returns
 * null on first call. The {@see SetTenantFromUser}
 * middleware re-runs the lookup after `auth:sanctum`, giving us a guaranteed
 * tenant for protected routes.
 */
class UserTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?IsTenant
    {
        $user = $request->user();

        if (! $user || ! $user->tenant_id) {
            return null;
        }

        return $user->tenant;
    }
}
