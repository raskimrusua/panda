<?php

use App\Models\CostEntry;
use App\Models\DiseaseDetection;
use App\Models\Season;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->actingAs($this->user);

    $this->season = Season::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->detection = DiseaseDetection::factory()->create([
        'tenant_id' => $this->tenant->id,
        'top_diagnosis' => 'Early Blight',
        'treatments' => [
            ['generic' => 'Mancozeb 80WP', 'pcpb' => 'Ridomil Gold MZ 68 WG', 'application_notes' => 'Spray every 7-10 days.'],
            ['generic' => 'Copper Hydroxide', 'pcpb' => 'Funguran-OH 50 WP'],
        ],
    ]);
});

it('creates a chemical CostEntry from a scan treatment', function () {
    $response = $this->postJson("/api/v1/disease/{$this->detection->id}/plan-treatment", [
        'season_id' => $this->season->id,
        'treatment_index' => 0,
        'amount_kes' => 450,
        'supplier_name' => 'Agrovet Ltd',
    ])
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'season_id', 'category', 'description', 'amount_kes', 'incurred_at']]);

    $cost = CostEntry::sole();
    expect($cost->tenant_id)->toBe($this->tenant->id)
        ->and($cost->season_id)->toBe($this->season->id)
        ->and($cost->category)->toBe(CostEntry::CATEGORY_CHEMICAL)
        ->and($cost->description)->toContain('Early Blight', 'Mancozeb 80WP', 'Ridomil Gold MZ 68 WG')
        ->and((float) $cost->amount_kes)->toBe(450.0)
        ->and($cost->supplier_name)->toBe('Agrovet Ltd')
        ->and($cost->logged_by)->toBe($this->user->id);
});

it('404s for a scan that belongs to another tenant', function () {
    $other = Tenant::factory()->create();
    $foreign = DiseaseDetection::factory()->create(['tenant_id' => $other->id]);

    $this->postJson("/api/v1/disease/{$foreign->id}/plan-treatment", [
        'season_id' => $this->season->id,
        'treatment_index' => 0,
        'amount_kes' => 450,
    ])->assertNotFound();

    expect(CostEntry::count())->toBe(0);
});

it('rejects a season that belongs to another tenant', function () {
    $other = Tenant::factory()->create();
    $foreignSeason = Season::factory()->create(['tenant_id' => $other->id]);

    $this->postJson("/api/v1/disease/{$this->detection->id}/plan-treatment", [
        'season_id' => $foreignSeason->id,
        'treatment_index' => 0,
        'amount_kes' => 450,
    ])->assertUnprocessable()->assertJsonValidationErrors(['season_id']);

    expect(CostEntry::count())->toBe(0);
});

it('rejects an out-of-range treatment index', function () {
    $this->postJson("/api/v1/disease/{$this->detection->id}/plan-treatment", [
        'season_id' => $this->season->id,
        'treatment_index' => 5,
        'amount_kes' => 450,
    ])->assertUnprocessable()->assertJsonValidationErrors(['treatment_index']);

    expect(CostEntry::count())->toBe(0);
});

it('rejects a missing amount', function () {
    $this->postJson("/api/v1/disease/{$this->detection->id}/plan-treatment", [
        'season_id' => $this->season->id,
        'treatment_index' => 0,
    ])->assertUnprocessable()->assertJsonValidationErrors(['amount_kes']);
});
