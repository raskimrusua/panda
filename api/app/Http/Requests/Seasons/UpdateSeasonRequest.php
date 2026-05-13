<?php

namespace App\Http\Requests\Seasons;

use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSeasonRequest extends FormRequest
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
            'acreage' => ['sometimes', 'numeric', 'min:0.01', 'max:1000'],
            'planting_date' => ['sometimes', 'date'],
            'status' => ['sometimes', Rule::in([
                Season::STATUS_PLANNING,
                Season::STATUS_ACTIVE,
                Season::STATUS_HARVESTING,
                Season::STATUS_COMPLETE,
                Season::STATUS_ABANDONED,
            ])],
            'irrigation_type' => ['sometimes', Rule::in([
                Season::IRRIGATION_RAINFED,
                Season::IRRIGATION_DRIP,
                Season::IRRIGATION_FURROW,
                Season::IRRIGATION_GREENHOUSE,
            ])],
        ];
    }
}
