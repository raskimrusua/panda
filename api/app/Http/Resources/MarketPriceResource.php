<?php

namespace App\Http\Resources;

use App\Models\MarketPrice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin MarketPrice
 */
class MarketPriceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'crop_id' => $this->crop_id,
            'market_name' => $this->market_name,
            'county' => $this->county,
            'observed_at' => $this->observed_at?->toDateString(),
            'grade' => $this->grade,
            'price_per_kg_kes' => (float) $this->price_per_kg_kes,
            'source' => $this->source,
        ];
    }
}
