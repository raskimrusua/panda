<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\InputListItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One scaled input recommendation per Season.
 * Tenant-scoped via BelongsToTenant.
 */
class InputListItem extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<InputListItemFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    public const TYPE_SEED = 'seed';

    public const TYPE_FERTILISER = 'fertiliser';

    public const TYPE_CHEMICAL = 'chemical';

    public const TYPE_EQUIPMENT = 'equipment';

    public const TYPE_OTHER = 'other';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'season_id',
        'input_type',
        'product_name',
        'quantity_per_acre',
        'quantity_scaled',
        'unit',
        'week_from_planting',
        'benchmark_price_kes',
        'cost_estimate_kes',
        'pcpb_registered',
        'alternatives',
        'procured_quantity',
        'procured_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_per_acre' => 'decimal:4',
            'quantity_scaled' => 'decimal:4',
            'week_from_planting' => 'integer',
            'benchmark_price_kes' => 'decimal:2',
            'cost_estimate_kes' => 'decimal:2',
            'pcpb_registered' => 'boolean',
            'alternatives' => 'array',
            'procured_quantity' => 'decimal:4',
            'procured_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['procured_quantity', 'procured_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
