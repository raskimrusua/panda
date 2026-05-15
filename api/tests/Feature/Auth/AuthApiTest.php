<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

it('registers a new farm + owner + token in one call', function () {
    $payload = [
        'farm_name' => 'Mwea Vegetables',
        'county' => 'Kirinyaga',
        'sub_county' => 'Mwea',
        'name' => 'Joseph Karanja',
        'email' => 'joseph@example.com',
        'password' => 'kale-acres-99',
        'password_confirmation' => 'kale-acres-99',
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ];

    $response = $this->postJson('/api/v1/auth/register', $payload);

    $response->assertCreated()
        ->assertJsonStructure(['user' => ['id', 'name', 'email', 'tenant_id', 'tenant'], 'token']);

    expect(Tenant::count())->toBe(1)
        ->and(User::count())->toBe(1);

    $user = User::first();
    expect($user->tenant_id)->toBe(Tenant::first()->id)
        ->and($user->email)->toBe('joseph@example.com');
});

it('rejects registration with duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'farm_name' => 'Some Farm',
        'county' => 'Meru',
        'name' => 'Anyone',
        'email' => 'taken@example.com',
        'password' => 'strong-pass-1',
        'password_confirmation' => 'strong-pass-1',
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('rejects registration with weak password', function () {
    $this->postJson('/api/v1/auth/register', [
        'farm_name' => 'Some Farm',
        'county' => 'Meru',
        'name' => 'Anyone',
        'email' => 'someone@example.com',
        'password' => 'weak',
        'password_confirmation' => 'weak',
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors(['password']);
});

it('logs in with correct credentials and returns a token', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'farmer@example.com',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'farmer@example.com',
        'password' => 'password',
        'device_name' => 'pwa',
    ]);

    $response->assertOk()
        ->assertJsonPath('user.id', $user->id);

    expect(PersonalAccessToken::count())->toBe(1);
});

it('rejects login with wrong password', function () {
    User::factory()->create(['email' => 'farmer@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'farmer@example.com',
        'password' => 'wrong-password',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('returns the authenticated user from /auth/me', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.tenant.id', $tenant->id);
});

it('rejects /auth/me when unauthenticated', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('logs out by deleting the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('pwa');

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/v1/auth/logout')
        ->assertOk();

    expect(PersonalAccessToken::count())->toBe(0);
});
