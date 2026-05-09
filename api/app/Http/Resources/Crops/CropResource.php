<?php

namespace App\Http\Resources\Crops;

use App\Models\Crop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * CropResource — single resource for shared catalogue model.
 *
 * Crop is shared across all tenants (read-only), so list + detail share
 * the same shape — no separate List/Detail pair needed (per skill-laravel-resource §4).
 *
 * Field discipline (per skill-laravel-resource):
 * - Explicit fields only — never parent::toArray()
 * - No deleted_at / system columns leaked
 * - Dates as ISO-8601, slug as URL-safe identifier
 *
 * @mixin Crop
 */
class CropResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name_en' => $this->name_en,
            'name_sw' => $this->name_sw,
            'category' => $this->category,
            'harvest_type' => $this->harvest_type,
            'image_url' => $this->image_url,
            'jica_manual_ref' => $this->jica_manual_ref,
            'phase_added' => (int) $this->phase_added,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
