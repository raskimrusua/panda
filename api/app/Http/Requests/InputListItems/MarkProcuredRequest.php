<?php

namespace App\Http\Requests\InputListItems;

use Illuminate\Foundation\Http\FormRequest;

class MarkProcuredRequest extends FormRequest
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
            'procured_quantity' => ['required', 'numeric', 'min:0'],
            'procured_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
