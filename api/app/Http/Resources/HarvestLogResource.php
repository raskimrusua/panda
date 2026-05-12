<?php

namespace App\Http\Resources;

use App\Models\HarvestLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HarvestLog
 */
class HarvestLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'harvested_at' => $this->harvested_at?->toDateString(),
            'quantity_kg' => $this->quantity_kg,
            'sold_quantity_kg' => $this->sold_quantity_kg,
            'unit_price_kes' => $this->unit_price_kes,
            'revenue_kes' => $this->revenueKes(),
            'buyer_name' => $this->buyer_name,
            'notes' => $this->notes,
            'photo_url' => $this->photo_url,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
