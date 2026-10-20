<?php

namespace App\Http\Resources;

use App\Models\TeamInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TeamInvitation
 */
class TeamInvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = match (true) {
            $this->revoked_at !== null => 'revoked',
            $this->accepted_at !== null => 'accepted',
            $this->expires_at->isPast() => 'expired',
            default => 'pending',
        };

        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'status' => $status,
            'expires_at' => $this->expires_at->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            'invited_by_name' => $this->whenLoaded('inviter', fn () => $this->inviter?->name),
        ];
    }
}
