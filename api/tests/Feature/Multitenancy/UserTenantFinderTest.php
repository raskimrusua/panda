<?php

use App\Models\Tenant;
use App\Models\User;
use App\Multitenancy\UserTenantFinder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('returns null when request has no authenticated user', function () {
    $finder = new UserTenantFinder;
    $tenant = $finder->findForRequest(Request::create('/'));

    expect($tenant)->toBeNull();
});

it('returns null when authenticated user has no tenant_id', function () {
    $user = User::factory()->withoutTenant()->create();

    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    $finder = new UserTenantFinder;
    expect($finder->findForRequest($request))->toBeNull();
});

it('returns the tenant when user has tenant_id', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $request = Request::create('/');
    $request->setUserResolver(fn () => $user);

    $finder = new UserTenantFinder;
    $resolved = $finder->findForRequest($request);

    expect($resolved)->not->toBeNull()
        ->and($resolved->id)->toBe($tenant->id);
});
