<?php

namespace App\Http\Controllers\Api\V1\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\AcceptInvitationRequest;
use App\Http\Requests\Team\InviteRequest;
use App\Http\Resources\TeamInvitationResource;
use App\Http\Resources\TeamMemberResource;
use App\Models\TeamInvitation;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\Team\TeamInvitationNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant-team management. List/invite/remove run inside the auth+tenant
 * middleware. `accept` is public (the token IS the credential) so it
 * runs outside the tenant middleware group; the invitation row tells us
 * which tenant to attach the new user to.
 */
class TeamController extends Controller
{
    /**
     * GET /api/v1/team — list current tenant's members + pending invites.
     * Available to all signed-in users (members can see who's on the team).
     */
    public function index(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $members = User::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at')
            ->get();

        $invitations = TeamInvitation::query()
            ->where('tenant_id', $tenantId)
            ->with('inviter')
            ->latest('created_at')
            ->get();

        return response()->json([
            'members' => TeamMemberResource::collection($members),
            'invitations' => TeamInvitationResource::collection($invitations),
        ]);
    }

    /**
     * POST /api/v1/team/invite — owner-only. Creates a TeamInvitation
     * with a 7-day expiry and dispatches the email. Idempotent on email:
     * a pending invite for the same email is reused (token rotates,
     * expiry extends) instead of creating duplicates.
     */
    public function invite(InviteRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $invitation = DB::transaction(function () use ($data, $user) {
            $existing = TeamInvitation::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('email', $data['email'])
                ->whereNull('accepted_at')
                ->whereNull('revoked_at')
                ->first();

            $invite = $existing ?? new TeamInvitation([
                'tenant_id' => $user->tenant_id,
                'invited_by' => $user->id,
                'email' => $data['email'],
            ]);

            $invite->fill([
                'name' => $data['name'] ?? null,
                'token' => TeamInvitation::generateToken(),
                'expires_at' => now()->addDays(7),
            ])->save();

            return $invite;
        });

        $tenantName = Tenant::query()->where('id', $user->tenant_id)->value('name') ?? 'your farm';
        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation, $user->name, $tenantName));

        return (new TeamInvitationResource($invitation->fresh('inviter')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * DELETE /api/v1/team/invitations/{invitation} — owner-only. Revokes
     * a pending invitation. Once revoked the token can no longer be used.
     * Uses the invitation id (not its token) so the owner-facing UI
     * doesn't have to expose tokens in JSON payloads.
     */
    public function revokeInvitation(Request $request, string $invitation): JsonResponse
    {
        if (! $request->user()->isOwner()) {
            return response()->json(['message' => 'Only the farm owner can revoke invitations.'], Response::HTTP_FORBIDDEN);
        }

        $invitation = TeamInvitation::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('id', $invitation)
            ->first();

        if (! $invitation) {
            return response()->json(['message' => 'Invitation not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($invitation->accepted_at !== null) {
            return response()->json(['message' => 'Invitation already accepted.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $invitation->update(['revoked_at' => now()]);

        return response()->json(['message' => 'Invitation revoked.']);
    }

    /**
     * DELETE /api/v1/team/{user} — owner-only. Removes a member from
     * the tenant. Cannot remove yourself or another owner.
     */
    public function removeMember(Request $request, string $userId): JsonResponse
    {
        if (! $request->user()->isOwner()) {
            return response()->json(['message' => 'Only the farm owner can remove members.'], Response::HTTP_FORBIDDEN);
        }

        $target = User::query()
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('id', $userId)
            ->first();

        if (! $target) {
            return response()->json(['message' => 'Member not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($target->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot remove yourself.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($target->isOwner()) {
            return response()->json(['message' => 'You cannot remove the farm owner.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $target->tokens()->delete();
        $target->delete();

        return response()->json(['message' => 'Member removed.']);
    }

    /**
     * POST /api/v1/team/accept/{token} — public. Creates a new User in
     * the tenant from the pending invitation; marks the invitation
     * accepted; returns the user + a Sanctum token so the PWA can sign
     * them in immediately.
     */
    public function accept(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        $invitation = TeamInvitation::query()->where('token', $token)->first();

        if (! $invitation || ! $invitation->isPending()) {
            return response()->json(['message' => 'This invitation is invalid or has expired.'], Response::HTTP_GONE);
        }

        $data = $request->validated();

        $user = DB::transaction(function () use ($invitation, $data) {
            $user = User::create([
                'tenant_id' => $invitation->tenant_id,
                'name' => $data['name'],
                'email' => $invitation->email,
                'password' => $data['password'],
                'role' => User::ROLE_MEMBER,
            ]);

            $invitation->update([
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ]);

            return $user;
        });

        $accessToken = $user->createToken('first-device')->plainTextToken;

        return response()->json([
            'user' => new TeamMemberResource($user),
            'token' => $accessToken,
        ], Response::HTTP_CREATED);
    }
}
