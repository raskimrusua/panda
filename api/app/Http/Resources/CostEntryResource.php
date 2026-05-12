<?php

namespace App\Http\Resources;

use App\Models\CostEntry;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CostEntry
 */
class CostEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'input_list_item_id' => $this->input_list_item_id,
            'category' => $this->category,
            'description' => $this->description,
            'amount_kes' => $this->amount_kes,
            'incurred_at' => $this->incurred_at?->toDateString(),
            'supplier_name' => $this->supplier_name,
            'receipt_url' => $this->receipt_url,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
