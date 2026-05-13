<?php

namespace App\Models;

use Database\Factories\DealerFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Agro-input shop. Shared catalogue (NOT tenant-scoped).
 *
 * Distance search is haversine in raw SQL — works on both Postgres and
 * SQLite. Pilot volume (30-50 dealers) doesn't justify PostGIS yet.
 *
 * @property float|null $distance_km Hydrated by DealerController::geoSearch — not a column.
 */
class Dealer extends Model
{
    /** @use HasFactory<DealerFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    public const STOCK_SEED = 'seed';

    public const STOCK_FERTILISER = 'fertiliser';

    public const STOCK_CHEMICAL = 'chemical';

    public const STOCK_EQUIPMENT = 'equipment';

    /** @var list<string> */
    protected $fillable = [
        'name',
        'slug',
        'county',
        'sub_county',
        'town',
        'gps_lat',
        'gps_lng',
        'phone',
        'whatsapp',
        'website',
        'stocks',
        'is_pcpb_certified',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'gps_lat' => 'float',
            'gps_lng' => 'float',
            'stocks' => 'array',
            'is_pcpb_certified' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'county', 'gps_lat', 'gps_lng', 'is_active', 'stocks'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
