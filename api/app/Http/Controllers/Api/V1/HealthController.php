<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Health\HealthCheck;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/v1/health/
 *
 * Public, no auth — uptime monitors hit this every minute. Returns 200 when
 * status is `ok` or `degraded`; 503 when any check failed. Body is small
 * enough to fit in one TCP packet — no over-fetch on a polling endpoint.
 */
class HealthController extends Controller
{
    public function __construct(private readonly HealthCheck $health) {}

    public function __invoke(): JsonResponse
    {
        $payload = $this->health->run();

        $code = $payload['status'] === HealthCheck::FAIL ? 503 : 200;

        return response()->json($payload, $code);
    }
}
