<?php

namespace App\Http\Requests\Disease;

use Illuminate\Foundation\Http\FormRequest;

class DetectRequest extends FormRequest
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
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 MB
            'crop_id' => ['nullable', 'string', 'ulid', 'exists:crops,id'],
            'season_id' => ['nullable', 'string', 'ulid', 'exists:seasons,id'],
            'opt_in_for_training' => ['nullable', 'boolean'],
        ];
    }
}
