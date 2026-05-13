<?php

namespace App\Models;

use Database\Factories\ContentReviewFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * Agronomist content review track for crop + disease JSON files.
 * NOT tenant-scoped — content is shared across all Panda farms.
 */
class ContentReview extends Model
{
    /** @use HasFactory<ContentReviewFactory> */
    use HasFactory;

    use LogsActivity;
    use SoftDeletes;

    public const TARGET_CROP = 'crop';

    public const TARGET_DISEASE = 'disease';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CHANGES_REQUESTED = 'changes_requested';

    /** @var list<string> */
    protected $fillable = [
        'target_type',
        'target_slug',
        'status',
        'content_payload',
        'reviewer_notes',
        'submitted_by',
        'reviewer_id',
        'submitted_at',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'content_payload' => 'array',
            'submitted_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['target_type', 'target_slug', 'status', 'reviewer_notes', 'submitted_by', 'reviewer_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SUBMITTED);
    }
}
