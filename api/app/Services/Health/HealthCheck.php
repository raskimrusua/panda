<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Throwable;

/**
 * Aggregates infra-level liveness checks into a single payload for the
 * `/api/v1/health/` endpoint. Every check returns 'ok', 'degraded' (warn
 * but not down), 'fail' (the check threw), or 'skipped' (deliberately
 * not wired yet — Crop.health stays skipped through P4).
 *
 * Overall status: `ok` if everything is ok-or-skipped; `degraded` if any
 * check is degraded; `fail` if any check failed. Controller maps that to
 * HTTP 200 / 200 / 503 — uptime monitors fire on 503.
 */
class HealthCheck
{
    public const OK = 'ok';

    public const DEGRADED = 'degraded';

    public const FAIL = 'fail';

    public const SKIPPED = 'skipped';

    /**
     * @return array{
     *     status: string,
     *     checks: array<string, string>,
     *     time: string
     * }
     */
    public function run(): array
    {
        $checks = [
            'db' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'crop_health' => self::SKIPPED,
        ];

        return [
            'status' => $this->aggregate($checks),
            'checks' => $checks,
            'time' => now()->toIso8601String(),
        ];
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo()->query('SELECT 1');

            return self::OK;
        } catch (Throwable) {
            return self::FAIL;
        }
    }

    private function checkRedis(): string
    {
        try {
            $pong = Redis::connection()->ping();

            // Redis::ping() returns true OR the string "PONG" depending on
            // client. Treat either as healthy.
            return ($pong === true || $pong === 'PONG' || $pong === '+PONG') ? self::OK : self::DEGRADED;
        } catch (Throwable) {
            return self::FAIL;
        }
    }

    /**
     * @param  array<string, string>  $checks
     */
    private function aggregate(array $checks): string
    {
        if (in_array(self::FAIL, $checks, true)) {
            return self::FAIL;
        }
        if (in_array(self::DEGRADED, $checks, true)) {
            return self::DEGRADED;
        }

        return self::OK;
    }
}
