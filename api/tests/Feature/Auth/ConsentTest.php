<?php

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

/*
| Kenya DPA 2019 consent gate — feature coverage for Panda.
|
| Mirrors Shira's tests/test_consent.py case-for-case. Where Shira asserts
| 400 (DRF validation), Panda asserts 422 (Laravel FormRequest); where
| Shira reads settings.TERMS_VERSION, Panda reads config('legal.*').
*/

const BASE_PAYLOAD = [
    'farm_name' => 'Test Farm',
    'county' => 'Meru',
    'name' => 'Test User',
    'email' => 'consent-test@example.com',
    'password' => 'strong-pass-1',
    'password_confirmation' => 'strong-pass-1',
];

it('rejects registration without terms_accepted (DPA §30)', function () {
    $this->postJson('/api/v1/auth/register', [
        ...BASE_PAYLOAD,
        'privacy_accepted' => true,
        // terms_accepted omitted
    ])->assertUnprocessable()->assertJsonValidationErrors(['terms_accepted']);

    expect(User::count())->toBe(0)
        ->and(UserConsent::count())->toBe(0);
});

it('rejects registration without privacy_accepted (DPA §30)', function () {
    $this->postJson('/api/v1/auth/register', [
        ...BASE_PAYLOAD,
        'terms_accepted' => true,
        // privacy_accepted omitted
    ])->assertUnprocessable()->assertJsonValidationErrors(['privacy_accepted']);

    expect(User::count())->toBe(0);
});

it('writes two UserConsent rows + stamps flat columns on successful register', function () {
    $payload = [
        ...BASE_PAYLOAD,
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ];

    $this->withHeader('User-Agent', 'PandaPWA/1.0 (iPhone; iOS 17)')
        ->postJson('/api/v1/auth/register', $payload)
        ->assertCreated();

    $user = User::firstWhere('email', 'consent-test@example.com');
    expect($user->terms_version)->toBe((string) config('legal.terms_version'))
        ->and($user->privacy_version)->toBe((string) config('legal.privacy_version'))
        ->and($user->terms_accepted_at)->not->toBeNull()
        ->and($user->privacy_accepted_at)->not->toBeNull();

    $consents = UserConsent::where('user_id', $user->id)->get();
    expect($consents)->toHaveCount(2)
        ->and($consents->pluck('policy_type')->sort()->values()->all())
            ->toBe(['privacy', 'terms']);

    // IP + UA captured for DPA §30 burden-of-proof audits.
    foreach ($consents as $c) {
        expect($c->ip_address)->not->toBeEmpty();
        expect($c->user_agent)->toBe('PandaPWA/1.0 (iPhone; iOS 17)');
    }
});

it('exposes current policy versions on the public /policies/active endpoint', function () {
    $response = $this->getJson('/api/v1/policies/active')->assertOk();

    $response->assertJsonStructure([
        'terms' => ['version', 'url'],
        'privacy' => ['version', 'url'],
    ]);
    expect($response->json('terms.version'))->toBe((string) config('legal.terms_version'))
        ->and($response->json('privacy.version'))->toBe((string) config('legal.privacy_version'));
});

it('ConsentGate emits 409 + TERMS_VERSION_OUTDATED for a stale user', function () {
    // Bump the current versions; the factory-defaulted user is now stale.
    Config::set('legal.terms_version', 'v2_2026-06-01');
    Config::set('legal.privacy_version', 'v2_2026-06-01');

    $tenant = Tenant::factory()->create();
    $user = User::factory()->stale()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/seasons')
        ->assertStatus(409)
        ->assertJsonPath('code', 'TERMS_VERSION_OUTDATED')
        ->assertJsonPath('required.terms_version', 'v2_2026-06-01')
        ->assertJsonPath('current.terms_version', null);
});

it('ConsentGate lets a fresh user through', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->getJson('/api/v1/seasons')
        ->assertOk();
});

it('/policies/accept stamps consent + idempotent on re-submit', function () {
    Config::set('legal.terms_version', 'v3_2026-07-01');
    Config::set('legal.privacy_version', 'v3_2026-07-01');

    $user = User::factory()->stale()->create();

    $payload = [
        'terms_version' => 'v3_2026-07-01',
        'privacy_version' => 'v3_2026-07-01',
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ];

    // First call: 2 UserConsent rows written, flat columns stamped.
    $this->actingAs($user)->postJson('/api/v1/policies/accept', $payload)->assertOk();

    $user->refresh();
    expect($user->terms_version)->toBe('v3_2026-07-01')
        ->and(UserConsent::where('user_id', $user->id)->count())->toBe(2);

    // Second call: same versions, still idempotent — no new rows, no error.
    $this->actingAs($user)->postJson('/api/v1/policies/accept', $payload)->assertOk();
    expect(UserConsent::where('user_id', $user->id)->count())->toBe(2);
});

it('/policies/accept rejects stale version submissions', function () {
    Config::set('legal.terms_version', 'v9_2026-12-01');

    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/policies/accept', [
        'terms_version' => 'v1_2026-05-15',     // old
        'privacy_version' => config('legal.privacy_version'),
        'terms_accepted' => true,
        'privacy_accepted' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors(['terms_version']);
});
