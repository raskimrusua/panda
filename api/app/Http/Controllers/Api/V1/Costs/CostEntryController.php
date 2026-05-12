<?php

namespace App\Http\Controllers\Api\V1\Costs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Costs\StoreCostEntryRequest;
use App\Http\Requests\Costs\UpdateCostEntryRequest;
use App\Http\Resources\CostEntryResource;
use App\Models\CostEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tenant-scoped — global BelongsToTenant scope handles isolation.
 * Cross-tenant lookups MUST 404 (per skill-laravel-multitenancy rule).
 */
class CostEntryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $costs = CostEntry::query()
            ->latest('incurred_at')
            ->paginate(50);

        return CostEntryResource::collection($costs);
    }

    public function store(StoreCostEntryRequest $request): JsonResponse
    {
        $cost = CostEntry::create($request->validated() + [
            'logged_by' => $request->user()?->id,
        ]);

        return (new CostEntryResource($cost))
            ->response()
            ->setStatusCode(201);
    }

    public function show(CostEntry $costEntry): CostEntryResource
    {
        return new CostEntryResource($costEntry);
    }

    public function update(UpdateCostEntryRequest $request, CostEntry $costEntry): CostEntryResource
    {
        $costEntry->update($request->validated());

        return new CostEntryResource($costEntry);
    }

    public function destroy(CostEntry $costEntry): JsonResponse
    {
        $costEntry->delete();

        return response()->json(null, 204);
    }
}
