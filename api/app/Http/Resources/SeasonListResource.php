<?php

namespace App\Http\Resources;

use App\Models\Season;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Minimal payload for /seasons index — fast lists, no relations expanded
 * beyond the joined crop's display name.
 *
 * @mixin Season
 */
class SeasonListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'crop_id' => $this->crop_id,
            'crop_name' => $this->whenLoaded('crop', fn () => $this->crop->name_en),
            'acreage' => $this->acreage,
            'planting_date' => $this->planting_date->toDateString(),
            'status' => $this->status,
            'irrigation_type' => $this->irrigation_type,
        ];
    }
}
