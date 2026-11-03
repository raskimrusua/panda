<?php

namespace App\Http\Requests\Disease;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DetectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `image` is the legacy single-photo field (older PWA builds still
     * send it); `images` is the Phase B multi-photo field (1-3). At least
     * one must be present — enforced in withValidator.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5 MB
            'images' => ['nullable', 'array', 'min:1', 'max:3'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'crop_id' => ['nullable', 'string', 'ulid', 'exists:crops,id'],
            // BOLA guard: the season must belong to the caller's tenant.
            'season_id' => ['nullable', 'string', 'ulid', Rule::exists('seasons', 'id')->where('tenant_id', $this->user()?->tenant_id)],
            'opt_in_for_training' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->file('image') && count($this->file('images') ?? []) === 0) {
                $validator->errors()->add('images', 'Provide at least one leaf photo.');
            }
        });
    }
}
