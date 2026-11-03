<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\DiseaseFeedbackFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * One farmer verdict on a disease scan. Phase A capture; Phase B consumes
 * rows for the correction retrain queue.
 */
class DiseaseFeedback extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<DiseaseFeedbackFactory> */
    use HasFactory;

    use HasUlids;
    use LogsActivity;

    public const VERDICT_CORRECT = 'correct';

    public const VERDICT_INCORRECT = 'incorrect';

    /** @var list<string> */
    protected $fillable = [
        'tenant_id',
        'disease_detection_id',
        'user_id',
        'verdict',
        'correction_key',
        'correction_text',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['verdict', 'correction_key'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<DiseaseDetection, $this> */
    public function diseaseDetection(): BelongsTo
    {
        return $this->belongsTo(DiseaseDetection::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
