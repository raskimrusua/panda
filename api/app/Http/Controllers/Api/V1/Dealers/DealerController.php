<?php

namespace App\Http\Controllers\Api\V1\Dealers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dealers\IndexDealerRequest;
use App\Http\Resources\DealerResource;
use App\Models\Dealer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Public dealer directory. NOT tenant-scoped — every farm sees the same
 * shared catalogue. Auth required (only authed app users hit it), but no
 * tenancy filter.
 *
 * Distance search is haversine in PHP (after a bounding-box SQL prefilter).
 * SQLite by default ships without trig functions (`acos`, `cos`, `sin`,
 * `radians`) and Laravel's local sqlite binary often doesn't include
 * --enable-math-functions, so a pure-SQL haversine returns NULL silently
 * and "5000 km away" dealers leak through. Bounding-box + PHP keeps the
 * math on the app side and works on every engine. Pilot volume (30-50
 * dealers) is fine; switch to PostGIS when the directory is in the
 * thousands.
 */
class DealerController extends Controller
{
    private const EARTH_RADIUS_KM = 6371.0;

    public function index(IndexDealerRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $perPage = (int) ($data['per_page'] ?? 25);

        $query = Dealer::query()->active();

        if (! empty($data['county'])) {
            $query->where('county', $data['county']);
        }
        if (! empty($data['stocks'])) {
            $query->whereJsonContains('stocks', $data['stocks']);
        }
        if (! empty($data['pcpb_only'])) {
            $query->where('is_pcpb_certified', true);
        }

        if (isset($data['lat'], $data['lng'])) {
            return $this->geoSearch($query, (float) $data['lat'], (float) $data['lng'], (float) ($data['radius_km'] ?? 100), $perPage);
        }

        $query->orderBy('name');

        return DealerResource::collection($query->paginate($perPage));
    }

    public function show(Dealer $dealer): DealerResource
    {
        return new DealerResource($dealer);
    }

    /**
     * @param  Builder<Dealer>  $query
     */
    private function geoSearch($query, float $lat, float $lng, float $radiusKm, int $perPage): AnonymousResourceCollection
    {
        // Bounding box: 1 deg lat ≈ 111 km, 1 deg lng ≈ 111*cos(lat) km.
        $latDelta = $radiusKm / 111.0;
        $lngDelta = $radiusKm / (111.0 * max(0.001, abs(cos(deg2rad($lat)))));

        $query
            ->whereBetween('gps_lat', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('gps_lng', [$lng - $lngDelta, $lng + $lngDelta]);

        $dealers = $query->get();

        // Refine with exact haversine in PHP, drop those outside radius.
        $withDistance = $dealers
            ->map(function (Dealer $d) use ($lat, $lng) {
                $d->distance_km = $this->haversine($lat, $lng, (float) $d->gps_lat, (float) $d->gps_lng);

                return $d;
            })
            ->filter(fn (Dealer $d) => $d->distance_km <= $radiusKm)
            ->sortBy('distance_km')
            ->values();

        // Manual paginate the in-memory collection.
        $page = (int) request('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginator = new LengthAwarePaginator(
            items: $withDistance->slice($offset, $perPage)->values(),
            total: $withDistance->count(),
            perPage: $perPage,
            currentPage: $page,
            options: ['path' => request()->url(), 'query' => request()->query()],
        );

        return DealerResource::collection($paginator);
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $latRad1 = deg2rad($lat1);
        $latRad2 = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos($latRad1) * cos($latRad2) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_KM * $c;
    }
}
