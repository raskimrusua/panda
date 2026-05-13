<?php

namespace App\Http\Resources;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Tenant
 */
class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'county' => $this->county,
            'sub_county' => $this->sub_county,
            'ward' => $this->ward,
            'gps_lat' => $this->gps_lat,
            'gps_lng' => $this->gps_lng,
        ];
    }
}
