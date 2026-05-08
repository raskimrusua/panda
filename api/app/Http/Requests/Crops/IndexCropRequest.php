<?php

namespace App\Http\Requests\Crops;

use Illuminate\Foundation\Http\FormRequest;

/**
 * IndexCropRequest — validates GET /api/v1/crops query parameters.
 *
 * Crop is the shared catalogue (no auth required; public farmer-facing browse).
 *
 * Whitelisted filters (per skill-laravel-controller §8 — never accept arbitrary
 * query params; allowlist explicit):
 * - category: vegetable | fruit | leafy_green | legume | staple | tuber
 * - harvest_type: single | multi
 * - phase: maximum build phase to include (defaults to 1)
 * - active_only: filter to is_active=true (defaults to true for public consumers)
 * - q: free-text search across name_en + name_sw + slug
 */
class IndexCropRequest extends FormRequest
{
    /** Crop catalogue is public — anyone can browse. */
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'string', 'in:vegetable,fruit,leafy_green,legume,staple,tuber'],
            'harvest_type' => ['nullable', 'string', 'in:single,multi'],
            'phase' => ['nullable', 'integer', 'between:1,3'],
            'active_only' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:80'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ];
    }

    /** Defaults applied before validation: assume active-only for safety. */
    protected function prepareForValidation(): void
    {
        if (! $this->has('active_only')) {
            $this->merge(['active_only' => true]);
        }
        if (! $this->has('phase')) {
            $this->merge(['phase' => 1]);
        }
    }
}
