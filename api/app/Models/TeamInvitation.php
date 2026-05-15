<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\TeamInvitationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $invited_by
 * @property string $email
 * @property ?string $name
 * @property string $token
 * @property Carbon $expires_at
 * @property ?Carbon $accepted_at
 * @property ?string $accepted_by
 * @property ?Carbon $revoked_at
 */
class TeamInvitation extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<TeamInvitationFactory> */
    use HasFactory;

    use HasUlids;

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'invited_by',
        'email',
        'name',
        'token',
        'expires_at',
        'accepted_at',
        'accepted_by',
        'revoked_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null
            && $this->revoked_at === null
            && $this->expires_at->isFuture();
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /** @return BelongsTo<User, $this> */
    public function accepter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
