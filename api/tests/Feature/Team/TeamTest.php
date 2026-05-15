<?php

use App\Models\TeamInvitation;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Team\TeamInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->owner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => User::ROLE_OWNER]);
});

it('GET /team returns members + invitations for the current tenant', function () {
    User::factory()->member()->create(['tenant_id' => $this->tenant->id]);
    TeamInvitation::factory()->create(['tenant_id' => $this->tenant->id, 'invited_by' => $this->owner->id]);

    // Noise from another tenant — must not appear.
    $other = Tenant::factory()->create();
    User::factory()->create(['tenant_id' => $other->id]);
    TeamInvitation::factory()->create(['tenant_id' => $other->id]);

    $this->actingAs($this->owner)
        ->getJson('/api/v1/team')
        ->assertOk()
        ->assertJsonCount(2, 'members')
        ->assertJsonCount(1, 'invitations');
});

it('POST /team/invite creates an invitation and sends the email (owner)', function () {
    Notification::fake();

    $response = $this->actingAs($this->owner)
        ->postJson('/api/v1/team/invite', [
            'email' => 'newbie@example.com',
            'name' => 'Newbie',
        ])->assertCreated()
        ->assertJsonPath('data.email', 'newbie@example.com')
        ->assertJsonPath('data.status', 'pending');

    $invitation = TeamInvitation::query()->where('email', 'newbie@example.com')->sole();
    expect($invitation->tenant_id)->toBe($this->tenant->id);

    Notification::assertSentOnDemand(TeamInvitationNotification::class);
});

it('POST /team/invite is forbidden for members', function () {
    $member = User::factory()->member()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($member)
        ->postJson('/api/v1/team/invite', ['email' => 'newbie@example.com'])
        ->assertForbidden();
});

it('POST /team/invite rejects an email already on the team', function () {
    User::factory()->create(['tenant_id' => $this->tenant->id, 'email' => 'inside@example.com']);

    $this->actingAs($this->owner)
        ->postJson('/api/v1/team/invite', ['email' => 'inside@example.com'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('POST /team/invite reuses a pending invite for the same email (rotates token)', function () {
    Notification::fake();

    $first = $this->actingAs($this->owner)
        ->postJson('/api/v1/team/invite', ['email' => 'pending@example.com'])
        ->assertCreated()
        ->json('data.id');

    $second = $this->actingAs($this->owner)
        ->postJson('/api/v1/team/invite', ['email' => 'pending@example.com'])
        ->assertCreated()
        ->json('data.id');

    expect($second)->toBe($first);
    expect(TeamInvitation::query()->where('email', 'pending@example.com')->count())->toBe(1);
});

it('POST /team/accept creates a member user and returns a token', function () {
    $invitation = TeamInvitation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'invited_by' => $this->owner->id,
        'email' => 'acceptor@example.com',
    ]);

    $response = $this->postJson("/api/v1/team/accept/{$invitation->token}", [
        'name' => 'Mama Acceptor',
        'password' => 'strong-password-1',
        'password_confirmation' => 'strong-password-1',
    ])->assertCreated()
        ->assertJsonStructure(['user' => ['id', 'email', 'role'], 'token']);

    expect($response->json('user.email'))->toBe('acceptor@example.com')
        ->and($response->json('user.role'))->toBe(User::ROLE_MEMBER);

    $user = User::query()->where('email', 'acceptor@example.com')->sole();
    expect($user->tenant_id)->toBe($this->tenant->id)
        ->and(Hash::check('strong-password-1', $user->password))->toBeTrue();

    expect($invitation->fresh()->accepted_at)->not->toBeNull()
        ->and($invitation->fresh()->accepted_by)->toBe($user->id);
});

it('POST /team/accept rejects an expired invitation with 410', function () {
    $invitation = TeamInvitation::factory()->expired()->create([
        'tenant_id' => $this->tenant->id,
        'invited_by' => $this->owner->id,
    ]);

    $this->postJson("/api/v1/team/accept/{$invitation->token}", [
        'name' => 'Valid Name',
        'password' => 'strong-password-1',
        'password_confirmation' => 'strong-password-1',
    ])->assertStatus(410);
});

it('POST /team/accept rejects a revoked invitation with 410', function () {
    $invitation = TeamInvitation::factory()->revoked()->create([
        'tenant_id' => $this->tenant->id,
        'invited_by' => $this->owner->id,
    ]);

    $this->postJson("/api/v1/team/accept/{$invitation->token}", [
        'name' => 'Valid Name',
        'password' => 'strong-password-1',
        'password_confirmation' => 'strong-password-1',
    ])->assertStatus(410);
});

it('DELETE /team/invitations/{invitation} revokes a pending invitation (owner only)', function () {
    $invitation = TeamInvitation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'invited_by' => $this->owner->id,
    ]);

    $this->actingAs($this->owner)
        ->deleteJson("/api/v1/team/invitations/{$invitation->id}")
        ->assertOk();

    expect($invitation->fresh()->revoked_at)->not->toBeNull();
});

it('DELETE /team/invitations is forbidden for members', function () {
    $member = User::factory()->member()->create(['tenant_id' => $this->tenant->id]);
    $invitation = TeamInvitation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'invited_by' => $this->owner->id,
    ]);

    $this->actingAs($member)
        ->deleteJson("/api/v1/team/invitations/{$invitation->id}")
        ->assertForbidden();
});

it('DELETE /team/{user} removes a member (owner only)', function () {
    $member = User::factory()->member()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($this->owner)
        ->deleteJson("/api/v1/team/{$member->id}")
        ->assertOk();

    expect(User::query()->find($member->id))->toBeNull();
});

it('DELETE /team/{user} cannot remove yourself', function () {
    $this->actingAs($this->owner)
        ->deleteJson("/api/v1/team/{$this->owner->id}")
        ->assertUnprocessable();
});

it('DELETE /team/{user} cannot remove the owner', function () {
    $secondOwner = User::factory()->create(['tenant_id' => $this->tenant->id, 'role' => User::ROLE_OWNER]);

    $this->actingAs($this->owner)
        ->deleteJson("/api/v1/team/{$secondOwner->id}")
        ->assertUnprocessable();
});

it('DELETE /team/{user} cannot remove a user from another tenant (404)', function () {
    $other = Tenant::factory()->create();
    $foreignUser = User::factory()->member()->create(['tenant_id' => $other->id]);

    $this->actingAs($this->owner)
        ->deleteJson("/api/v1/team/{$foreignUser->id}")
        ->assertNotFound();

    expect(User::query()->find($foreignUser->id))->not->toBeNull();
});
