<?php

namespace App\Http\Resources;

use App\Models\Dealer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Dealer
 */
class DealerResource extends JsonResource
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
            'town' => $this->town,
            'gps_lat' => $this->gps_lat,
            'gps_lng' => $this->gps_lng,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'website' => $this->website,
            'stocks' => $this->stocks,
            'is_pcpb_certified' => $this->is_pcpb_certified,
            'distance_km' => isset($this->distance_km) ? round((float) $this->distance_km, 2) : null,
        ];
    }
}
