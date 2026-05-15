<?php

namespace App\Models;

use App\Notifications\Auth\ResetPasswordPwa;
use App\Notifications\Auth\VerifyEmailPwa;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasUlids;
    use Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_MEMBER = 'member';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'is_superuser',
        'role',
    ];

    /** @var list<string> */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superuser' => 'boolean',
        ];
    }

    /**
     * Filament panel access — superuser-only. Farmers (the vast majority of
     * users) cannot reach `/admin`. Ops + agronomists are flipped via tinker
     * or by another superuser inside the panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_superuser === true;
    }

    /** @return BelongsTo<Tenant, $this> */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    /**
     * Override the default verification notification so the link in the
     * email points at the PWA (where the user lives), not the API
     * hostname. PWA forwards the signed params back to the API.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailPwa);
    }

    /**
     * Override the default password-reset notification for the same
     * reason — the link must land in the PWA.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordPwa($token));
    }
}
