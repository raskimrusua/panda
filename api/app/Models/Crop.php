<?php

namespace App\Models;

use Database\Factories\CropFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Crop — shared catalogue model (NOT tenant-scoped).
 *
 * One row per agronomic crop in Panda's library (17 JICA SHEP PLUS crops in P1).
 * The DB row is intentionally thin: identity + taxonomy + lifecycle.
 * The full agronomic content (timeline, varieties, inputs, diseases) lives in
 * resources/content/crops/<slug>.json, loaded into Redis at startup by ContentLoader.
 *
 * @property string $id (ULID)
 * @property string $slug
 * @property string $name_en
 * @property string $name_sw
 * @property string $category
 * @property string $harvest_type
 * @property ?string $image_url
 * @property ?string $jica_manual_ref
 * @property int $phase_added
 * @property bool $is_active
 */
class Crop extends Model
{
    /** @use HasFactory<CropFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'name_en',
        'name_sw',
        'category',
        'harvest_type',
        'image_url',
        'jica_manual_ref',
        'phase_added',
        'is_active',
    ];

    protected $hidden = ['deleted_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'phase_added' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Scope to active crops — used by farmer-facing API.
     *
     * @param  Builder<Crop>  $query
     * @return Builder<Crop>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to crops available in the current build phase (or earlier).
     *
     * @param  Builder<Crop>  $query
     * @return Builder<Crop>
     */
    public function scopeInPhase(Builder $query, int $maxPhase): Builder
    {
        return $query->where('phase_added', '<=', $maxPhase);
    }
}
