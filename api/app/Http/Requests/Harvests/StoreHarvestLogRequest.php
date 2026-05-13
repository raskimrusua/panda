<?php

namespace App\Http\Requests\Harvests;

use Illuminate\Foundation\Http\FormRequest;

class StoreHarvestLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'season_id' => ['required', 'string', 'ulid', 'exists:seasons,id'],
            'harvested_at' => ['required', 'date', 'before_or_equal:today'],
            'quantity_kg' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'sold_quantity_kg' => ['nullable', 'numeric', 'min:0', 'lte:quantity_kg'],
            'unit_price_kes' => ['nullable', 'numeric', 'min:0', 'max:99999.99', 'required_with:sold_quantity_kg'],
            'buyer_name' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'photo_url' => ['nullable', 'url', 'max:500'],
            'client_id' => ['nullable', 'string', 'ulid'],
        ];
    }
}
