<?php

namespace App\Http\Requests\Costs;

use App\Models\CostEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCostEntryRequest extends FormRequest
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
            'category' => ['sometimes', Rule::in([
                CostEntry::CATEGORY_SEED,
                CostEntry::CATEGORY_FERTILISER,
                CostEntry::CATEGORY_CHEMICAL,
                CostEntry::CATEGORY_LABOUR,
                CostEntry::CATEGORY_EQUIPMENT,
                CostEntry::CATEGORY_TRANSPORT,
                CostEntry::CATEGORY_OTHER,
            ])],
            'description' => ['sometimes', 'string', 'min:1', 'max:200'],
            'amount_kes' => ['sometimes', 'numeric', 'min:0.01', 'max:9999999.99'],
            'incurred_at' => ['sometimes', 'date', 'before_or_equal:today'],
            'supplier_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'receipt_url' => ['sometimes', 'nullable', 'url', 'max:500'],
        ];
    }
}
