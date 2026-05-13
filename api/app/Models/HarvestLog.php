<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\HarvestLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One harvest event for a Season. Multi-pick crops produce many; single-
 * harvest crops produce one. The HarvestLog observer keeps a rolling
 * cumulative + projected revenue stamped on Season.engine_metadata.
 *
 * Tenant-scoped.
 */
class HarvestLog extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<HarvestLogFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'season_id',
        'harvested_at',
        'quantity_kg',
        'sold_quantity_kg',
        'unit_price_kes',
        'buyer_name',
        'notes',
        'photo_url',
        'client_id',
        'logged_by',
    ];

    protected function casts(): array
    {
        return [
            'harvested_at' => 'date',
            'quantity_kg' => 'decimal:2',
            'sold_quantity_kg' => 'decimal:2',
            'unit_price_kes' => 'decimal:2',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['harvested_at', 'quantity_kg', 'sold_quantity_kg', 'unit_price_kes', 'buyer_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** Revenue from this row only (sold portion × unit price). */
    public function revenueKes(): float
    {
        if ($this->unit_price_kes === null) {
            return 0.0;
        }

        return round((float) $this->sold_quantity_kg * (float) $this->unit_price_kes, 2);
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<User, $this> */
    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
