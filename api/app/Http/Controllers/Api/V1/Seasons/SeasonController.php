<?php

namespace App\Http\Controllers\Api\V1\Seasons;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seasons\StoreSeasonRequest;
use App\Http\Requests\Seasons\UpdateSeasonRequest;
use App\Http\Resources\SeasonDetailResource;
use App\Http\Resources\SeasonListResource;
use App\Models\Season;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tenant-scoped — every read/write filters by current tenant via the global
 * BelongsToTenant scope. Cross-tenant lookups MUST 404, never 403 (Rule from
 * skill-laravel-multitenancy: 403 leaks resource existence).
 */
class SeasonController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $seasons = Season::query()
            ->with('crop')
            ->latest('planting_date')
            ->paginate(25);

        return SeasonListResource::collection($seasons);
    }

    public function store(StoreSeasonRequest $request): JsonResponse
    {
        $season = Season::create($request->validated());

        return (new SeasonDetailResource($season->load('crop')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Season $season): SeasonDetailResource
    {
        return new SeasonDetailResource($season->load('crop'));
    }

    public function update(UpdateSeasonRequest $request, Season $season): SeasonDetailResource
    {
        $season->update($request->validated());

        return new SeasonDetailResource($season->load('crop'));
    }

    public function destroy(Season $season): JsonResponse
    {
        $season->delete();

        return response()->json(null, 204);
    }
}
