<?php

namespace App\Http\Controllers\Api\V1\Disease;

use App\Http\Controllers\Controller;
use App\Http\Requests\Disease\DetectRequest;
use App\Http\Requests\Disease\PlanTreatmentRequest;
use App\Http\Resources\CostEntryResource;
use App\Http\Resources\DiseaseDetectionResource;
use App\Models\CostEntry;
use App\Models\Crop;
use App\Models\DiseaseDetection;
use App\Services\Crops\Disease\CropHealthClient;
use App\Services\Storage\ImageUploadService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * Download a scan as a shareable PDF (crop, diagnosis, confidence,
     * PCPB treatment plan + advisory disclaimer). Route-model binding is
     * tenant-scoped, so foreign-tenant scans 404 before this runs.
     */
    public function report(DiseaseDetection $diseaseDetection): Response
    {
        $providerLabels = [
            DiseaseDetection::PROVIDER_MOCK => 'Demo mode',
            DiseaseDetection::PROVIDER_CROP_HEALTH => 'Crop.health (Kindwise)',
            DiseaseDetection::PROVIDER_OFFLINE_TREE => 'Offline decision tree',
        ];

        /** @var list<array{generic: string, pcpb?: ?string, application_notes?: string}> $treatments */
        $treatments = $diseaseDetection->treatments;

        $pdf = Pdf::loadView('pdf.disease-report', [
            'detection' => $diseaseDetection,
            'crop' => $diseaseDetection->crop,
            'season' => $diseaseDetection->season,
            'providerLabel' => $providerLabels[$diseaseDetection->provider] ?? (string) $diseaseDetection->provider,
            'confidencePct' => $diseaseDetection->confidence !== null
                ? (int) round((float) $diseaseDetection->confidence * 100)
                : null,
            'treatments' => $treatments,
            'disclaimer' => (string) config('legal.disease_disclaimer'),
        ]);

        return $pdf->download(sprintf('panda-disease-scan-%s.pdf', $diseaseDetection->id));
    }

    /**
     * Turn a scan's treatment recommendation into a chemical CostEntry on
     * the chosen season. Route-model binding is tenant-scoped
     * (BelongsToTenant), so foreign-tenant scans 404 before this runs.
     */
    public function planTreatment(PlanTreatmentRequest $request, DiseaseDetection $diseaseDetection): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        /** @var list<array{generic: string, pcpb?: ?string, application_notes?: string}> $treatments */
        $treatments = $diseaseDetection->treatments;
        $index = (int) $data['treatment_index'];
        $treatment = $treatments[$index] ?? null;

        if ($treatment === null) {
            return response()->json([
                'message' => 'The given treatment index is not valid for this scan.',
                'errors' => ['treatment_index' => ['No treatment at this index.']],
            ], 422);
        }

        $description = trim(($diseaseDetection->top_diagnosis ?? 'Disease scan').' — '.$treatment['generic']);
        if (! empty($treatment['pcpb'])) {
            $description .= ' ('.$treatment['pcpb'].')';
        }

        $cost = CostEntry::create([
            'tenant_id' => $user->tenant_id,
            'season_id' => $data['season_id'],
            'category' => CostEntry::CATEGORY_CHEMICAL,
            'description' => $description,
            'amount_kes' => $data['amount_kes'],
            'incurred_at' => $data['incurred_at'] ?? now(),
            'supplier_name' => $data['supplier_name'] ?? null,
            'logged_by' => $user->id,
        ]);

        return (new CostEntryResource($cost))
            ->response()
            ->setStatusCode(201);
    }
}
