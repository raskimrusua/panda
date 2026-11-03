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
 * rows for the correction retrain queue (`reviewed_*` stamps + export).
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
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['verdict', 'correction_key', 'reviewed_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Stamp the row as pulled into a training batch. NULL reviewed_at = still
     * in the retrain queue (the Filament triage filter keys off this).
     */
    public function markReviewed(User $user): static
    {
        $this->reviewed_at = now();
        $this->reviewed_by = $user->id;
        $this->save();

        return $this;
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

    /** @return BelongsTo<User, $this> */
    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
