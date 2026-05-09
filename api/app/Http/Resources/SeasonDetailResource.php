<?php

namespace App\Http\Resources;

use App\Http\Resources\Crops\CropResource;
use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full Season detail — includes engine_metadata, joined Crop, timestamps.
 *
 * @mixin Season
 */
class SeasonDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'crop_id' => $this->crop_id,
            'crop' => new CropResource($this->whenLoaded('crop')),
            'acreage' => $this->acreage,
            'planting_date' => $this->planting_date->toDateString(),
            'status' => $this->status,
            'irrigation_type' => $this->irrigation_type,
            'engine_metadata' => $this->engine_metadata,
            'client_id' => $this->client_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
