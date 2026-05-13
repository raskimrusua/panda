<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\SeasonActivityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One row per engine-generated step in a Season's timeline.
 * Tenant-scoped: every read filters by current tenant via BelongsToTenant.
 */
class SeasonActivity extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<SeasonActivityFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_DONE = 'done';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_OVERDUE = 'overdue';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'season_id',
        'activity_type',
        'phase',
        'ideal_date',
        'week_from_planting',
        'day_window',
        'description_en',
        'description_sw',
        'tip_en',
        'tip_sw',
        'is_critical',
        'status',
        'completed_at',
        'completed_by',
        'completion_notes',
    ];

    protected function casts(): array
    {
        return [
            'ideal_date' => 'date',
            'week_from_planting' => 'integer',
            'day_window' => 'integer',
            'is_critical' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'completed_at', 'completed_by', 'completion_notes'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<User, $this> */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }
}
