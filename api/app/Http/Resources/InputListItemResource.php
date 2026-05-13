<?php

namespace App\Http\Resources;

use App\Models\InputListItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InputListItem
 */
class InputListItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'input_type' => $this->input_type,
            'product_name' => $this->product_name,
            'quantity_per_acre' => $this->quantity_per_acre,
            'quantity_scaled' => $this->quantity_scaled,
            'unit' => $this->unit,
            'week_from_planting' => $this->week_from_planting,
            'benchmark_price_kes' => $this->benchmark_price_kes,
            'cost_estimate_kes' => $this->cost_estimate_kes,
            'pcpb_registered' => $this->pcpb_registered,
            'alternatives' => $this->alternatives,
            'procured_quantity' => $this->procured_quantity,
            'procured_at' => $this->procured_at?->toIso8601String(),
        ];
    }
}
