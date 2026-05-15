<?php

namespace App\Http\Controllers\Api\V1\Disease;

use App\Http\Controllers\Controller;
use App\Http\Requests\Disease\DetectRequest;
use App\Http\Resources\DiseaseDetectionResource;
use App\Models\Crop;
use App\Models\DiseaseDetection;
use App\Services\Crops\Disease\CropHealthClient;
use App\Services\Storage\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Disease detection endpoints.
 *
 * `POST /api/v1/disease/detect` — accept image upload, save via
 * ImageUploadService (default disk = R2 in prod, local in dev), call
 * CropHealthClient (mock through P4), persist DiseaseDetection, return
 * resource. Tenant-scoped via the model's BelongsToTenant.
 *
 * `GET /api/v1/disease/history` — list own-tenant scans.
 *
 * `GET /api/v1/disease/{id}` — show one scan.
 */
class DiseaseDetectionController extends Controller
{
    public function __construct(
        private readonly CropHealthClient $client,
        private readonly ImageUploadService $images,
    ) {}

    public function detect(DetectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $tenantId = $request->user()->tenant_id;

        $cropSlug = null;
        if (! empty($data['crop_id'])) {
            $cropSlug = Crop::query()->where('id', $data['crop_id'])->value('slug');
        }

        // Reserve the row first so HasUlids can assign the primary key; the
        // path then uses the real id (not a pre-generated one that the trait
        // would overwrite). image_path is nullable in this transaction window
        // — restored by the upload + save below before commit.
        $detection = DB::transaction(function () use ($request, $data, $cropSlug) {
            $detection = new DiseaseDetection([
                'season_id' => $data['season_id'] ?? null,
                'crop_id' => $data['crop_id'] ?? null,
                'image_path' => 'pending',
                'provider' => DiseaseDetection::PROVIDER_MOCK,
                'opt_in_for_training' => (bool) ($data['opt_in_for_training'] ?? false),
                'captured_by' => $request->user()?->id,
                'captured_at' => now(),
            ]);
            $detection->save();

            $imagePath = $this->images->uploadDiseasePhoto(
                $request->file('image'),
                $request->user()->tenant_id,
                (string) $detection->id,
            );

            $result = $this->client->diagnose($imagePath, $cropSlug);

            $detection->fill([
                'image_path' => $imagePath,
                'provider' => $result->provider,
                'top_diagnosis' => $result->topDiagnosis,
                'confidence' => $result->confidence,
                'engine_response' => $result->rawResponse,
                'treatments' => $result->treatments,
            ])->save();

            return $detection;
        });

        return (new DiseaseDetectionResource($detection))
            ->response()
            ->setStatusCode(201);
    }

    public function history(): AnonymousResourceCollection
    {
        $scans = DiseaseDetection::query()
            ->latest('captured_at')
            ->paginate(25);

        return DiseaseDetectionResource::collection($scans);
    }

    public function show(DiseaseDetection $diseaseDetection): DiseaseDetectionResource
    {
        return new DiseaseDetectionResource($diseaseDetection);
    }
}
