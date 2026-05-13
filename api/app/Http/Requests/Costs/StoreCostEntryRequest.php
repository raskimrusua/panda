<?php

namespace App\Http\Requests\Costs;

use App\Models\CostEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCostEntryRequest extends FormRequest
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
            'input_list_item_id' => ['nullable', 'string', 'ulid', 'exists:input_list_items,id'],
            'category' => ['required', Rule::in([
                CostEntry::CATEGORY_SEED,
                CostEntry::CATEGORY_FERTILISER,
                CostEntry::CATEGORY_CHEMICAL,
                CostEntry::CATEGORY_LABOUR,
                CostEntry::CATEGORY_EQUIPMENT,
                CostEntry::CATEGORY_TRANSPORT,
                CostEntry::CATEGORY_OTHER,
            ])],
            'description' => ['required', 'string', 'min:1', 'max:200'],
            'amount_kes' => ['required', 'numeric', 'min:0.01', 'max:9999999.99'],
            'incurred_at' => ['required', 'date', 'before_or_equal:today'],
            'supplier_name' => ['nullable', 'string', 'max:120'],
            'receipt_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}
