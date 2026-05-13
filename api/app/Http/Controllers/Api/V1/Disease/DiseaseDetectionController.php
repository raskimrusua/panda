<?php

namespace App\Http\Controllers\Api\V1\Disease;

use App\Http\Controllers\Controller;
use App\Http\Requests\Disease\DetectRequest;
use App\Http\Resources\DiseaseDetectionResource;
use App\Models\Crop;
use App\Models\DiseaseDetection;
use App\Services\Crops\Disease\CropHealthClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

/**
 * Disease detection endpoints.
 *
 * `POST /api/v1/disease/detect` — accept image upload, save to Storage,
 * call CropHealthClient (mock through P4), persist DiseaseDetection,
 * return resource. Tenant-scoped via the model's BelongsToTenant.
 *
 * `GET /api/v1/disease/history` — list own-tenant scans (PWA's
 * "previous diagnoses" tab).
 *
 * `GET /api/v1/disease/{id}` — show one scan.
 */
class DiseaseDetectionController extends Controller
{
    public function __construct(private readonly CropHealthClient $client) {}

    public function detect(DetectRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Save image to disk first (Storage facade switches local/R2/S3).
        $imagePath = $request->file('image')->store('disease-scans', 'public');
        $imageUrl = Storage::disk('public')->url($imagePath);

        // Crop slug helps the adapter narrow the candidate diseases.
        $cropSlug = null;
        if (! empty($data['crop_id'])) {
            $cropSlug = Crop::query()->where('id', $data['crop_id'])->value('slug');
        }

        $result = $this->client->diagnose($imagePath, $cropSlug);

        $detection = DiseaseDetection::create([
            'season_id' => $data['season_id'] ?? null,
            'crop_id' => $data['crop_id'] ?? null,
            'image_url' => $imageUrl,
            'provider' => $result->provider,
            'top_diagnosis' => $result->topDiagnosis,
            'confidence' => $result->confidence,
            'engine_response' => $result->rawResponse,
            'treatments' => $result->treatments,
            'opt_in_for_training' => (bool) ($data['opt_in_for_training'] ?? false),
            'captured_by' => $request->user()?->id,
            'captured_at' => now(),
        ]);

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
