<?php

use App\Models\Dealer;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DealerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->actingAs($this->user);
});

it('rejects unauthenticated requests', function () {
    auth()->logout();
    $this->getJson('/api/v1/dealers')->assertUnauthorized();
});

it('lists active dealers paginated', function () {
    Dealer::factory()->count(15)->create();
    Dealer::factory()->count(3)->inactive()->create();

    $this->getJson('/api/v1/dealers')
        ->assertOk()
        ->assertJsonCount(15, 'data');
});

it('filters by county', function () {
    Dealer::factory()->count(3)->create(['county' => 'Meru']);
    Dealer::factory()->count(5)->create(['county' => 'Nairobi']);

    $this->getJson('/api/v1/dealers?county=Meru')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('filters by stocks (JSON contains)', function () {
    Dealer::factory()->create(['stocks' => ['seed', 'fertiliser']]);
    Dealer::factory()->create(['stocks' => ['chemical', 'equipment']]);
    Dealer::factory()->create(['stocks' => ['seed']]);

    $this->getJson('/api/v1/dealers?stocks=seed')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('filters by pcpb_only', function () {
    Dealer::factory()->count(3)->create(['is_pcpb_certified' => true]);
    Dealer::factory()->count(2)->create(['is_pcpb_certified' => false]);

    $this->getJson('/api/v1/dealers?pcpb_only=1')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('haversine search returns dealers within radius sorted by distance', function () {
    // Meru centre approx (0.0463, 37.6559)
    $near = Dealer::factory()->inMeru()->create(['name' => 'Closest']);
    $further = Dealer::factory()->create([
        'name' => 'Further',
        'gps_lat' => 0.50,
        'gps_lng' => 37.95,
    ]);
    $tooFar = Dealer::factory()->create([
        'name' => 'Too Far',
        'gps_lat' => 1.50,
        'gps_lng' => 35.00,
    ]);

    $response = $this->getJson('/api/v1/dealers?lat=0.0463&lng=37.6559&radius_km=200')
        ->assertOk();

    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toContain('Closest')
        ->and($names)->toContain('Further')
        ->and($names)->not->toContain('Too Far')
        ->and($names[0])->toBe('Closest'); // sorted ascending by distance

    // distance_km present + in expected ranges
    $first = $response->json('data.0');
    expect((float) $first['distance_km'])->toBeLessThan(20);
});

it('rejects gps_lat without gps_lng', function () {
    $this->getJson('/api/v1/dealers?lat=0.0463')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['lng']);
});

it('rejects out-of-Kenya gps coordinates', function () {
    $this->getJson('/api/v1/dealers?lat=51.5&lng=0.1') // London
        ->assertUnprocessable();
});

it('seeder lands 30 dealers idempotently', function () {
    // Need crops first since the seeder has no FKs to crops, just verify run works
    (new DealerSeeder)->run();
    expect(Dealer::count())->toBe(30);

    // Re-running doesn't duplicate
    (new DealerSeeder)->run();
    expect(Dealer::count())->toBe(30);
});

it('show returns a single dealer', function () {
    $dealer = Dealer::factory()->create();

    $this->getJson("/api/v1/dealers/{$dealer->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $dealer->id);
});
