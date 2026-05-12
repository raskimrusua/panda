<?php

namespace App\Http\Resources;

use App\Models\SeasonActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin SeasonActivity
 */
class SeasonActivityResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'activity_type' => $this->activity_type,
            'phase' => $this->phase,
            'ideal_date' => $this->ideal_date?->toDateString(),
            'week_from_planting' => $this->week_from_planting,
            'day_window' => $this->day_window,
            'description_en' => $this->description_en,
            'description_sw' => $this->description_sw,
            'tip_en' => $this->tip_en,
            'tip_sw' => $this->tip_sw,
            'is_critical' => $this->is_critical,
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'completion_notes' => $this->completion_notes,
        ];
    }
}
