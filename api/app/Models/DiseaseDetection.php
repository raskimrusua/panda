<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\DiseaseDetectionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One disease scan from a farmer. Tenant-scoped.
 */
class DiseaseDetection extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<DiseaseDetectionFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;
    use SoftDeletes;

    public const PROVIDER_MOCK = 'mock';

    public const PROVIDER_CROP_HEALTH = 'crop_health';

    public const PROVIDER_OFFLINE_TREE = 'offline_decision_tree';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'season_id',
        'crop_id',
        'image_url',
        'provider',
        'top_diagnosis',
        'confidence',
        'engine_response',
        'treatments',
        'opt_in_for_training',
        'captured_by',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'confidence' => 'decimal:4',
            'engine_response' => 'array',
            'treatments' => 'array',
            'opt_in_for_training' => 'boolean',
            'captured_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['provider', 'top_diagnosis', 'confidence', 'crop_id', 'season_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<Crop, $this> */
    public function crop(): BelongsTo
    {
        return $this->belongsTo(Crop::class);
    }

    /** @return BelongsTo<Season, $this> */
    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
