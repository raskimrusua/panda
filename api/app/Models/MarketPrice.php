<?php

namespace App\Models;

use Database\Factories\MarketPriceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One observed market price for a (crop, market, date, grade) tuple.
 * Shared catalogue (NOT tenant-scoped). Append-only — historical prices
 * are never edited, only superseded by newer rows.
 *
 * No soft-delete: a wrong row is fixed by inserting a corrected one with
 * a higher source-trust score. Activity log not needed because the table
 * is bulk-imported, not edited.
 */
class MarketPrice extends Model
{
    /** @use HasFactory<MarketPriceFactory> */
    use HasFactory;

    use HasUlids;

    public const SOURCE_ADMIN_CSV = 'admin_csv';

    public const SOURCE_AMIS_KENYA = 'amis_kenya';

    public const SOURCE_FIELD_AGENT = 'field_agent';

    /** @var list<string> */
    protected $fillable = [
        'crop_id',
        'market_name',
        'county',
        'observed_at',
        'grade',
        'price_per_kg_kes',
        'source',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'observed_at' => 'date',
            'price_per_kg_kes' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Crop, $this> */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }
}
