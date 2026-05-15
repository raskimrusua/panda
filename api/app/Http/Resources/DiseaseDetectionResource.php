<?php

namespace App\Http\Resources;

use App\Models\DiseaseDetection;
use App\Services\Storage\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DiseaseDetection
 */
class DiseaseDetectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'crop_id' => $this->crop_id,
            'image_url' => $this->image_path
                ? app(ImageUploadService::class)->temporaryUrl($this->image_path, 15)
                : null,
            'provider' => $this->provider,
            'top_diagnosis' => $this->top_diagnosis,
            'confidence' => $this->confidence !== null ? (float) $this->confidence : null,
            'treatments' => $this->treatments,
            'opt_in_for_training' => $this->opt_in_for_training,
            'captured_at' => $this->captured_at?->toIso8601String(),
        ];
    }
}
