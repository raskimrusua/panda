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
            // Aggregated public-source prices lag the market by 1–3 days; the
            // disclaimer ships in the payload so PWA-offline + any programmatic
            // consumer sees it. Source of truth: config('legal.price_disclaimer').
            'disclaimer' => (string) config('legal.price_disclaimer'),
        ];
    }
}
