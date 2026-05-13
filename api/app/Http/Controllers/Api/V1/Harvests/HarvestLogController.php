<?php

namespace App\Http\Controllers\Api\V1\Harvests;

use App\Http\Controllers\Controller;
use App\Http\Requests\Harvests\StoreHarvestLogRequest;
use App\Http\Requests\Harvests\UpdateHarvestLogRequest;
use App\Http\Resources\HarvestLogResource;
use App\Models\HarvestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tenant-scoped via global BelongsToTenant scope.
 *
 * `client_id` (when present) makes store idempotent: a duplicate sync from
 * the offline queue returns the existing row rather than creating a second.
 */
class HarvestLogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $logs = HarvestLog::query()
            ->latest('harvested_at')
            ->paginate(50);

        return HarvestLogResource::collection($logs);
    }

    public function store(StoreHarvestLogRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Idempotency: same client_id from a re-sync returns the prior row
        // (the unique [tenant_id, client_id] index would otherwise 422).
        if (! empty($data['client_id'])) {
            $existing = HarvestLog::query()
                ->where('client_id', $data['client_id'])
                ->first();
            if ($existing !== null) {
                return (new HarvestLogResource($existing))
                    ->response()
                    ->setStatusCode(200);
            }
        }

        $log = HarvestLog::create($data + [
            'logged_by' => $request->user()?->id,
        ]);

        return (new HarvestLogResource($log))
            ->response()
            ->setStatusCode(201);
    }

    public function show(HarvestLog $harvestLog): HarvestLogResource
    {
        return new HarvestLogResource($harvestLog);
    }

    public function update(UpdateHarvestLogRequest $request, HarvestLog $harvestLog): HarvestLogResource
    {
        $harvestLog->update($request->validated());

        return new HarvestLogResource($harvestLog);
    }

    public function destroy(HarvestLog $harvestLog): JsonResponse
    {
        $harvestLog->delete();

        return response()->json(null, 204);
    }
}
