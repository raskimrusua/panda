<?php

namespace App\Http\Requests\Disease;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Offline decision-tree result upload (Phase B3 pilot). No image — the
 * tree answer trail is the evidence. `client_id` is the PWA queue's
 * idempotency key (mirrors Season/HarvestLog offline sync).
 */
class OfflineSyncRequest extends FormRequest
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
            'crop_id' => ['nullable', 'string', 'ulid', 'exists:crops,id'],
            'season_id' => ['nullable', 'string', 'ulid', Rule::exists('seasons', 'id')->where('tenant_id', $this->user()?->tenant_id)],
            'top_diagnosis' => ['required', 'string', 'max:200'],
            'answers' => ['nullable', 'array', 'max:20'],
            'answers.*' => ['string', 'max:200'],
            'client_id' => ['nullable', 'string', 'max:40'],
            'captured_at' => ['nullable', 'date'],
        ];
    }
}
