<?php

namespace App\Http\Requests\Activities;

use Illuminate\Foundation\Http\FormRequest;

class LogDoneRequest extends FormRequest
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
            'completion_notes' => ['nullable', 'string', 'max:2000'],
            'completed_at' => ['nullable', 'date', 'before_or_equal:now'],
        ];
    }
}
