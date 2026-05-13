<?php

use App\Services\Health\HealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

uses(RefreshDatabase::class);

it('returns 200 with status=ok when DB and Redis are healthy', function () {
    Redis::shouldReceive('connection->ping')->andReturn('PONG');

    $this->getJson('/api/v1/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.db', 'ok')
        ->assertJsonPath('checks.redis', 'ok')
        ->assertJsonPath('checks.crop_health', 'skipped')
        ->assertJsonStructure(['status', 'checks' => ['db', 'redis', 'crop_health'], 'time']);
});

it('returns 503 when the database check fails', function () {
    DB::shouldReceive('connection->getPdo->query')->andThrow(new RuntimeException('db down'));
    Redis::shouldReceive('connection->ping')->andReturn('PONG');

    $this->getJson('/api/v1/health')
        ->assertStatus(503)
        ->assertJsonPath('status', 'fail')
        ->assertJsonPath('checks.db', 'fail');
});

it('returns 503 when the redis check fails', function () {
    Redis::shouldReceive('connection->ping')->andThrow(new RuntimeException('redis down'));

    $this->getJson('/api/v1/health')
        ->assertStatus(503)
        ->assertJsonPath('status', 'fail')
        ->assertJsonPath('checks.redis', 'fail');
});

it('endpoint is public — no auth required', function () {
    Redis::shouldReceive('connection->ping')->andReturn('PONG');

    // No actingAs() — verifies the route is unauthenticated.
    $this->getJson('/api/v1/health')->assertOk();
});

it('HealthCheck service marks crop_health as skipped (mocked through P4)', function () {
    Redis::shouldReceive('connection->ping')->andReturn('PONG');

    $payload = (new HealthCheck)->run();

    expect($payload['checks']['crop_health'])->toBe('skipped');
});
