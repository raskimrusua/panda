<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SeasonFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Season — one farmer's plan to grow one Crop on N acres from a planting date.
 * Tenant-scoped: every read filters on the current tenant via BelongsToTenant.
 */
class Season extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<SeasonFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    public const STATUS_PLANNING = 'planning';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_HARVESTING = 'harvesting';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_ABANDONED = 'abandoned';

    public const IRRIGATION_RAINFED = 'rainfed';

    public const IRRIGATION_DRIP = 'drip';

    public const IRRIGATION_FURROW = 'furrow';

    public const IRRIGATION_GREENHOUSE = 'greenhouse';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'crop_id',
        'acreage',
        'planting_date',
        'status',
        'irrigation_type',
        'engine_metadata',
        'client_id',
    ];

    protected function casts(): array
    {
        return [
            'acreage' => 'decimal:2',
            'planting_date' => 'date',
            'engine_metadata' => 'array',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['crop_id', 'acreage', 'planting_date', 'status', 'irrigation_type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Crop, $this> */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    /** @return HasMany<SeasonActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(SeasonActivity::class)->orderBy('ideal_date');
    }

    /** @return HasMany<InputListItem, $this> */
    public function inputListItems(): HasMany
    {
        return $this->hasMany(InputListItem::class)->orderBy('week_from_planting');
    }

    /** @return HasMany<CostEntry, $this> */
    public function costEntries(): HasMany
    {
        return $this->hasMany(CostEntry::class)->orderBy('incurred_at');
    }

    /** @return HasMany<HarvestLog, $this> */
    public function harvestLogs(): HasMany
    {
        return $this->hasMany(HarvestLog::class)->orderBy('harvested_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_HARVESTING]);
    }
}
