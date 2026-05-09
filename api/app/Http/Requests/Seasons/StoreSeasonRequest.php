<?php

namespace App\Http\Requests\Seasons;

use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSeasonRequest extends FormRequest
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
            'crop_id' => ['required', 'string', 'exists:crops,id'],
            'acreage' => ['required', 'numeric', 'min:0.01', 'max:1000'],
            'planting_date' => ['required', 'date'],
            'status' => ['nullable', Rule::in([
                Season::STATUS_PLANNING,
                Season::STATUS_ACTIVE,
            ])],
            'irrigation_type' => ['nullable', Rule::in([
                Season::IRRIGATION_RAINFED,
                Season::IRRIGATION_DRIP,
                Season::IRRIGATION_FURROW,
                Season::IRRIGATION_GREENHOUSE,
            ])],
            'client_id' => ['nullable', 'string', 'ulid'],
        ];
    }
}
