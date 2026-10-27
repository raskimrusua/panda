<?php

namespace App\Http\Requests\Disease;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST /api/v1/disease/{diseaseDetection}/plan-treatment — turn a scan's
 * treatment recommendation into a real CostEntry on the chosen season.
 */
class PlanTreatmentRequest extends FormRequest
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
            // BOLA guard: the season must belong to the caller's tenant.
            'season_id' => ['required', 'string', 'ulid', Rule::exists('seasons', 'id')->where('tenant_id', $this->user()?->tenant_id)],
            'treatment_index' => ['required', 'integer', 'min:0', 'max:19'],
            'amount_kes' => ['required', 'numeric', 'min:0', 'max:1000000'],
            'supplier_name' => ['nullable', 'string', 'max:190'],
            'incurred_at' => ['nullable', 'date'],
        ];
    }
}
