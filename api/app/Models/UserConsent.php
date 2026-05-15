<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Auditable record that a user accepted a specific version of a policy.
 *
 * Kenya DPA 2019 §30 — the data controller must demonstrate that consent
 * was given. One row per (user, policy_type, version); the table is
 * append-only (see the `updating` model event below — throws to enforce
 * the invariant at the application layer).
 *
 * The flat `terms_accepted_at` / `terms_version` columns on User are the
 * fast-check surface for the ConsentGate middleware; UserConsent is the
 * audit trail kept for ODPC compliance.
 */
class UserConsent extends Model
{
    use HasFactory;
    use HasUlids;

    public const POLICY_TERMS = 'terms';

    public const POLICY_PRIVACY = 'privacy';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'policy_type',
        'version',
        'ip_address',
        'user_agent',
    ];

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Append-only: throw on any update. Re-acceptance of the same
        // (user, policy, version) is caught by the unique constraint
        // upstream (firstOrCreate in AcceptPoliciesController).
        static::updating(function (UserConsent $consent): void {
            throw new \LogicException(
                'UserConsent is append-only — refusing to update id='.$consent->id
            );
        });
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
