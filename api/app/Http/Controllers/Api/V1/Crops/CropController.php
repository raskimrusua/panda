<?php

namespace App\Http\Controllers\Api\V1\Crops;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crops\IndexCropRequest;
use App\Http\Resources\Crops\CropResource;
use App\Models\Crop;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * CropController — public read-only catalogue.
 *
 * Crop is shared across all tenants (no farm scope). API is index + show only;
 * write operations happen via Filament admin (skill-filament-admin).
 *
 * Pattern: thin controller per skill-laravel-controller —
 * 1. FormRequest accepts validated query params
 * 2. Eloquent query with allowlisted filters
 * 3. CropResource shapes the response
 *
 * Slug is the public identifier (not id), so route binds {crop:slug}.
 */
class CropController extends Controller
{
    public function index(IndexCropRequest $request): AnonymousResourceCollection
    {
        $params = $request->validated();

        $crops = Crop::query()
            ->when($params['active_only'] ?? true, fn ($q) => $q->active())
            ->when(isset($params['phase']), fn ($q) => $q->inPhase((int) $params['phase']))
            ->when(! empty($params['category']), fn ($q) => $q->where('category', $params['category']))
            ->when(! empty($params['harvest_type']), fn ($q) => $q->where('harvest_type', $params['harvest_type']))
            ->when(! empty($params['q']), function ($q) use ($params) {
                $needle = '%'.$params['q'].'%';
                $q->where(function ($q2) use ($needle) {
                    $q2->where('name_en', 'like', $needle)
                        ->orWhere('name_sw', 'like', $needle)
                        ->orWhere('slug', 'like', $needle);
                });
            })
            ->orderBy('name_en')
            ->paginate($params['per_page'] ?? 20);

        return CropResource::collection($crops);
    }

    /** Slug-bound: GET /api/v1/crops/{slug} where {slug} matches Crop.slug. */
    public function show(Crop $crop): CropResource
    {
        // Route model binding via slug (configured below in routes/api.php).
        // Hidden if soft-deleted (default scope) or inactive crop only visible if explicitly requested.
        return new CropResource($crop);
    }
}
