<?php

namespace App\Http\Requests\Harvests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHarvestLogRequest extends FormRequest
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
            'harvested_at' => ['sometimes', 'date', 'before_or_equal:today'],
            'quantity_kg' => ['sometimes', 'numeric', 'min:0.01', 'max:9999999.99'],
            'sold_quantity_kg' => ['sometimes', 'numeric', 'min:0'],
            'unit_price_kes' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:99999.99'],
            'buyer_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'photo_url' => ['sometimes', 'nullable', 'url', 'max:500'],
        ];
    }
}
