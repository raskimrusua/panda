<?php

namespace App\Http\Resources;

use App\Models\DiseaseFeedback;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DiseaseFeedback
 */
class DiseaseFeedbackResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'disease_detection_id' => $this->disease_detection_id,
            'verdict' => $this->verdict,
            'correction_key' => $this->correction_key,
            'correction_text' => $this->correction_text,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
