<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\CostEntryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Actual cost incurred against a Season. Tenant-scoped.
 */
class CostEntry extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<CostEntryFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    public const CATEGORY_SEED = 'seed';

    public const CATEGORY_FERTILISER = 'fertiliser';

    public const CATEGORY_CHEMICAL = 'chemical';

    public const CATEGORY_LABOUR = 'labour';

    public const CATEGORY_EQUIPMENT = 'equipment';

    public const CATEGORY_TRANSPORT = 'transport';

    public const CATEGORY_OTHER = 'other';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'season_id',
        'input_list_item_id',
        'category',
        'description',
        'amount_kes',
        'incurred_at',
        'supplier_name',
        'receipt_url',
        'logged_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_kes' => 'decimal:2',
            'incurred_at' => 'date',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['category', 'description', 'amount_kes', 'incurred_at', 'supplier_name', 'input_list_item_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    /** @return BelongsTo<InputListItem, $this> */
    public function inputListItem(): BelongsTo
    {
        return $this->belongsTo(InputListItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function logger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_by');
    }
}
