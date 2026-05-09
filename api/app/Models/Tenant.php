<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

/**
 * Panda Tenant = Farm.
 *
 * Single-DB row-based multitenancy: every business model carries a tenant_id
 * and is auto-scoped via BelongsToTenant. We extend Spatie's base Tenant so
 * the ::current() helper, MakeTenantCurrentAction, and queue-tenant-awareness
 * still work — but we replace the domain-based finder with UserTenantFinder.
 */
class Tenant extends SpatieTenant
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'county',
        'sub_county',
        'ward',
        'gps_lat',
        'gps_lng',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'gps_lat' => 'float',
            'gps_lng' => 'float',
            'settings' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'county', 'sub_county', 'ward'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
