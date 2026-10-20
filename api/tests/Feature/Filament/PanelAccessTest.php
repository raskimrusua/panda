<?php

use App\Filament\Resources\ContentReviewResource;
use App\Filament\Resources\CropResource;
use App\Filament\Resources\DealerResource;
use App\Filament\Resources\MarketPriceResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('non-superuser is redirected away from /admin', function () {
    $farmer = User::factory()->create(['is_superuser' => false]);

    $this->actingAs($farmer)
        ->get('/admin')
        ->assertForbidden();
});

it('unauthenticated visit to /admin redirects to login', function () {
    $this->get('/admin')->assertRedirect();
});

it('superuser can reach the panel', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get('/admin')
        ->assertSuccessful();
});

it('CropResource is registered with the admin panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)->toContain(CropResource::class);
});

it('ContentReviewResource is registered with the admin panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)->toContain(ContentReviewResource::class);
});

it('DealerResource is registered with the admin panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)->toContain(DealerResource::class);
});

it('MarketPriceResource is registered with the admin panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)->toContain(MarketPriceResource::class);
});

it('User::canAccessPanel returns true only for superusers', function () {
    $farmer = User::factory()->create(['is_superuser' => false]);
    $admin = User::factory()->superuser()->create();
    $panel = Filament::getPanel('admin');

    expect($farmer->canAccessPanel($panel))->toBeFalse()
        ->and($admin->canAccessPanel($panel))->toBeTrue();
});
