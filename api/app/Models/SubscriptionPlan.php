<?php

namespace App\Models;

use Database\Factories\SubscriptionPlanFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * SubscriptionPlan — shared pricing catalogue (NOT tenant-scoped).
 * Mirrors Shira's apps/billing/models.py SubscriptionPlan.
 */
class SubscriptionPlan extends Model
{
    /** @use HasFactory<SubscriptionPlanFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;

    public const BILLING_MONTHLY = 'monthly';

    public const BILLING_YEARLY = 'yearly';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'price_kes',
        'billing_cycle',
        'features',
        'is_active',
        'sort_order',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price_kes' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price_kes', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function isFree(): bool
    {
        return $this->price_kes <= 0;
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
