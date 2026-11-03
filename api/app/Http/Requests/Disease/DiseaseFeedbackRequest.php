<?php

namespace App\Http\Requests\Disease;

use App\Models\DiseaseFeedback;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DiseaseFeedbackRequest extends FormRequest
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
            'verdict' => ['required', 'string', Rule::in([DiseaseFeedback::VERDICT_CORRECT, DiseaseFeedback::VERDICT_INCORRECT])],
            'correction_key' => ['nullable', 'string', 'max:100'],
            'correction_text' => ['nullable', 'string', 'max:500'],
        ];
    }
}
