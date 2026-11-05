<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\PersonalAccessToken;

uses(RefreshDatabase::class);

const TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';

function fakeTokeninfo(array $payload, int $status = 200): void
{
    Http::fake([
        TOKENINFO_URL.'*' => Http::response($payload, $status),
    ]);
}

beforeEach(function () {
    config(['google.oauth_client_id' => 'test-client-id.apps.googleusercontent.com']);
});

it('logs in an existing user with a valid Google credential', function () {
    $user = User::factory()->create(['email' => 'farmer@gmail.com']);

    fakeTokeninfo([
        'aud' => 'test-client-id.apps.googleusercontent.com',
        'email' => 'farmer@gmail.com',
        'email_verified' => 'true',
        'sub' => 'google-sub-123',
    ]);

    $response = $this->postJson('/api/v1/auth/google', [
        'credential' => 'fake-google-id-token',
        'device_name' => 'pwa',
    ]);

    $response->assertOk()
        ->assertJsonStructure(['user' => ['id', 'email'], 'token']);

    expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(1);
});

it('rejects a credential whose audience is a different client', function () {
    User::factory()->create(['email' => 'farmer@gmail.com']);

    fakeTokeninfo([
        'aud' => 'someone-elses-client.apps.googleusercontent.com',
        'email' => 'farmer@gmail.com',
        'email_verified' => 'true',
        'sub' => 'google-sub-123',
    ]);

    $this->postJson('/api/v1/auth/google', ['credential' => 'fake-google-id-token'])
        ->assertUnauthorized();

    expect(PersonalAccessToken::count())->toBe(0);
});

it('rejects an unverified Google email', function () {
    fakeTokeninfo([
        'aud' => 'test-client-id.apps.googleusercontent.com',
        'email' => 'unverified@gmail.com',
        'email_verified' => 'false',
        'sub' => 'google-sub-456',
    ]);

    $this->postJson('/api/v1/auth/google', ['credential' => 'fake-google-id-token'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['credential']);
});

it('returns 404 when the Google email has no Panda account', function () {
    fakeTokeninfo([
        'aud' => 'test-client-id.apps.googleusercontent.com',
        'email' => 'nobody@gmail.com',
        'email_verified' => 'true',
        'sub' => 'google-sub-789',
    ]);

    $this->postJson('/api/v1/auth/google', ['credential' => 'fake-google-id-token'])
        ->assertNotFound()
        ->assertJsonPath('message', 'No Panda account found for this Google email. Register first.');
});

it('returns 503 when Google OAuth is not configured', function () {
    config(['google.oauth_client_id' => '']);

    $this->postJson('/api/v1/auth/google', ['credential' => 'fake-google-id-token'])
        ->assertServiceUnavailable();
});

it('returns 401 when Google rejects the token', function () {
    fakeTokeninfo(['error_description' => 'Invalid Value'], 400);

    $this->postJson('/api/v1/auth/google', ['credential' => 'garbage'])
        ->assertUnauthorized();
});

it('never creates a user or tenant from a Google credential', function () {
    fakeTokeninfo([
        'aud' => 'test-client-id.apps.googleusercontent.com',
        'email' => 'newperson@gmail.com',
        'email_verified' => 'true',
        'sub' => 'google-sub-999',
    ]);

    $this->postJson('/api/v1/auth/google', ['credential' => 'fake-google-id-token'])
        ->assertNotFound();

    expect(User::count())->toBe(0);
});

it('validates the credential field shape', function () {
    $this->postJson('/api/v1/auth/google', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['credential']);
});

it('sends the id_token as a query parameter to tokeninfo', function () {
    User::factory()->create(['email' => 'farmer@gmail.com']);
    $sent = null;

    Http::fake(function (ClientRequest $request) use (&$sent) {
        $sent = $request;
        return Http::response([
            'aud' => 'test-client-id.apps.googleusercontent.com',
            'email' => 'farmer@gmail.com',
            'email_verified' => 'true',
            'sub' => 'google-sub-123',
        ]);
    });

    $this->postJson('/api/v1/auth/google', ['credential' => 'tok-abc'])->assertOk();

    expect($sent)->not->toBeNull()
        ->and($sent->url())->toContain('id_token=tok-abc');
});
