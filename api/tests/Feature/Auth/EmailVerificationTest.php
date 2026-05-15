<?php

use App\Models\User;
use App\Notifications\Auth\VerifyEmailPwa;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('registration dispatches the verification email', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Mama Wanjiru',
        'email' => 'wanjiru@example.com',
        'password' => 'super-secret-123',
        'password_confirmation' => 'super-secret-123',
        'farm_name' => 'Wanjiru Farm',
        'county' => 'Meru',
    ])->assertCreated();

    $user = User::where('email', 'wanjiru@example.com')->sole();
    Notification::assertSentTo($user, VerifyEmailPwa::class);
});

it('verify endpoint marks the email verified and redirects to PWA on success', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();
    $signed = URL::temporarySignedRoute(
        'verification.verify',
        now()->addHour(),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $relative = parse_url($signed, PHP_URL_PATH).'?'.parse_url($signed, PHP_URL_QUERY);

    $response = $this->get($relative);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/verified');
    expect($user->fresh()->email_verified_at)->not->toBeNull();
    Event::assertDispatched(Verified::class);
});

it('verify endpoint with wrong hash redirects to PWA with error', function () {
    $user = User::factory()->unverified()->create();
    $signed = URL::temporarySignedRoute(
        'verification.verify',
        now()->addHour(),
        ['id' => $user->id, 'hash' => sha1('wrong@example.com')],
    );

    $relative = parse_url($signed, PHP_URL_PATH).'?'.parse_url($signed, PHP_URL_QUERY);

    $response = $this->get($relative);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('error=invalid');
    expect($user->fresh()->email_verified_at)->toBeNull();
});

it('verify endpoint rejects unsigned links with 403', function () {
    $user = User::factory()->unverified()->create();

    $this->get("/api/v1/auth/email/verify/{$user->id}/".sha1($user->email))
        ->assertForbidden();
});

it('sendVerification re-sends the verification email for an authenticated unverified user', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/auth/email/verification-notification')
        ->assertOk();

    Notification::assertSentTo($user, VerifyEmailPwa::class);
});

it('sendVerification returns 204 when the user is already verified', function () {
    Notification::fake();
    $user = User::factory()->create(); // verified by default

    $this->actingAs($user)
        ->postJson('/api/v1/auth/email/verification-notification')
        ->assertNoContent();

    Notification::assertNothingSent();
});
