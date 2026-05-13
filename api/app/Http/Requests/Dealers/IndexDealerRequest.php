<?php

namespace App\Http\Requests\Dealers;

use Illuminate\Foundation\Http\FormRequest;

class IndexDealerRequest extends FormRequest
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
            'lat' => ['nullable', 'numeric', 'between:-5,5', 'required_with:lng'],
            'lng' => ['nullable', 'numeric', 'between:33,42', 'required_with:lat'],
            'radius_km' => ['nullable', 'numeric', 'min:1', 'max:1000'],
            'county' => ['nullable', 'string', 'max:64'],
            'stocks' => ['nullable', 'string', 'in:seed,fertiliser,chemical,equipment'],
            'pcpb_only' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
