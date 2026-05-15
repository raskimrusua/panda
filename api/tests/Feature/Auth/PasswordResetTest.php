<?php

use App\Models\User;
use App\Notifications\Auth\ResetPasswordPwa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('forgotPassword dispatches the reset email for an existing user', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'mama@example.com']);

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'mama@example.com'])
        ->assertOk()
        ->assertJsonStructure(['message']);

    Notification::assertSentTo($user, ResetPasswordPwa::class);
});

it('forgotPassword returns 200 for nonexistent emails (no enumeration)', function () {
    Notification::fake();

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'ghost@example.com'])
        ->assertOk();

    Notification::assertNothingSent();
});

it('resetPassword updates the password when given a valid token', function () {
    $user = User::factory()->create(['email' => 'mama@example.com']);
    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/password/reset', [
        'token' => $token,
        'email' => 'mama@example.com',
        'password' => 'new-strong-password',
        'password_confirmation' => 'new-strong-password',
    ])->assertOk();

    expect(Hash::check('new-strong-password', $user->fresh()->password))->toBeTrue();
});

it('resetPassword rejects an invalid token with 422', function () {
    $user = User::factory()->create(['email' => 'mama@example.com']);
    $oldHash = $user->password;

    $this->postJson('/api/v1/auth/password/reset', [
        'token' => 'nonsense-token',
        'email' => 'mama@example.com',
        'password' => 'new-strong-password',
        'password_confirmation' => 'new-strong-password',
    ])->assertUnprocessable();

    expect($user->fresh()->password)->toBe($oldHash);
});

it('resetPassword enforces password confirmation', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    $this->postJson('/api/v1/auth/password/reset', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-strong-password',
        'password_confirmation' => 'mismatch',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});
