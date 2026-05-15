<?php

use App\Models\User;
use App\Notifications\Auth\VerifyEmailPwa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('updates the name without touching email_verified_at', function () {
    $user = User::factory()->create(['name' => 'Old Name']);
    $verifiedAt = $user->email_verified_at;

    $this->actingAs($user)
        ->patchJson('/api/v1/auth/profile', ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');

    expect((string) $user->fresh()->email_verified_at?->toIso8601String())
        ->toBe((string) $verifiedAt?->toIso8601String());
});

it('changing the email clears verification and dispatches a fresh email', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'old@example.com']);

    $this->actingAs($user)
        ->patchJson('/api/v1/auth/profile', ['email' => 'new@example.com'])
        ->assertOk()
        ->assertJsonPath('data.email', 'new@example.com')
        ->assertJsonPath('data.email_verified_at', null);

    $user->refresh();
    expect($user->email)->toBe('new@example.com')
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmailPwa::class);
});

it('rejects an email already taken by another user', function () {
    $other = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patchJson('/api/v1/auth/profile', ['email' => 'taken@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);

    expect($user->fresh()->email)->not->toBe('taken@example.com');
    expect($other->fresh()->email)->toBe('taken@example.com');
});

it('changes the password when the current password is correct', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password-1')]);

    $this->actingAs($user)
        ->patchJson('/api/v1/auth/password', [
            'current_password' => 'old-password-1',
            'password' => 'new-password-2',
            'password_confirmation' => 'new-password-2',
        ])->assertOk();

    expect(Hash::check('new-password-2', $user->fresh()->password))->toBeTrue();
});

it('rejects the password change when the current password is wrong', function () {
    $user = User::factory()->create(['password' => Hash::make('old-password-1')]);
    $oldHash = $user->password;

    $this->actingAs($user)
        ->patchJson('/api/v1/auth/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password-2',
            'password_confirmation' => 'new-password-2',
        ])->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);

    expect($user->fresh()->password)->toBe($oldHash);
});

it('rejects the password change when new equals current', function () {
    $user = User::factory()->create(['password' => Hash::make('same-pw-1')]);

    $this->actingAs($user)
        ->patchJson('/api/v1/auth/password', [
            'current_password' => 'same-pw-1',
            'password' => 'same-pw-1',
            'password_confirmation' => 'same-pw-1',
        ])->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
});

it('profile endpoints require authentication', function () {
    $this->patchJson('/api/v1/auth/profile', ['name' => 'X'])->assertUnauthorized();
    $this->patchJson('/api/v1/auth/password', [])->assertUnauthorized();
});
