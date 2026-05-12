<?php

use App\Models\Crop;
use App\Models\InputListItem;
use App\Models\Season;
use App\Models\SeasonActivity;
use App\Models\Tenant;
use App\Services\Crops\SeasonEngine\SeasonEngine;
use App\Services\Crops\SeasonEngine\SeasonEngineInput;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeEngineInput(string $cropSlug = 'tomato', float $acreage = 1.0, string $irrigation = 'rainfed'): SeasonEngineInput
{
    return new SeasonEngineInput(
        cropSlug: $cropSlug,
        tenantId: '01ABCDEFGHJKMNPQRSTVWXYZ00',
        seasonId: '01ABCDEFGHJKMNPQRSTVWXYZ01',
        acreage: $acreage,
        plantingDate: CarbonImmutable::parse('2026-06-01'),
        irrigationType: $irrigation,
    );
}

it('throws when crop slug is unknown', function () {
    $engine = app(SeasonEngine::class);
    expect(fn () => $engine->generate(makeEngineInput('not-a-real-crop')))
        ->toThrow(InvalidArgumentException::class);
});

it('generates the full tomato timeline + inputs at 1 acre', function () {
    /** @var SeasonEngine $engine */
    $engine = app(SeasonEngine::class);
    $output = $engine->generate(makeEngineInput('tomato', 1.0));

    expect($output->activities)->not->toBeEmpty()
        ->and($output->inputs)->not->toBeEmpty()
        ->and(count($output->activities))->toBeGreaterThanOrEqual(10)
        ->and(count($output->inputs))->toBeGreaterThanOrEqual(5);

    // Activity dates should be derived from planting_date + week_from_planting
    $firstActivity = $output->activities[0];
    expect($firstActivity->idealDate)->toBeInstanceOf(CarbonImmutable::class);
});

it('scales input quantities linearly with acreage at 0.5 acres', function () {
    $engine = app(SeasonEngine::class);

    $oneAcre = $engine->generate(makeEngineInput('tomato', 1.0));
    $halfAcre = $engine->generate(makeEngineInput('tomato', 0.5));

    expect(count($halfAcre->inputs))->toBe(count($oneAcre->inputs));

    foreach ($oneAcre->inputs as $i => $oneAcreInput) {
        expect($halfAcre->inputs[$i]->quantityScaled)
            ->toBe(round($oneAcreInput->quantityScaled / 2, 4));
    }
});

it('scales input quantities linearly with acreage at 3 acres', function () {
    $engine = app(SeasonEngine::class);

    $oneAcre = $engine->generate(makeEngineInput('tomato', 1.0));
    $threeAcre = $engine->generate(makeEngineInput('tomato', 3.0));

    foreach ($oneAcre->inputs as $i => $oneAcreInput) {
        expect($threeAcre->inputs[$i]->quantityScaled)
            ->toBe(round($oneAcreInput->quantityScaled * 3, 4));
    }
});

it('greenhouse irrigation cuts pesticide quantities by 40%', function () {
    $engine = app(SeasonEngine::class);

    $rainfed = $engine->generate(makeEngineInput('tomato', 1.0, 'rainfed'));
    $greenhouse = $engine->generate(makeEngineInput('tomato', 1.0, 'greenhouse'));

    $rainfedChemicals = collect($rainfed->inputs)->where('inputType', 'chemical')->values();
    $greenhouseChemicals = collect($greenhouse->inputs)->where('inputType', 'chemical')->values();

    expect($rainfedChemicals)->not->toBeEmpty()
        ->and($greenhouseChemicals)->toHaveCount($rainfedChemicals->count());

    foreach ($rainfedChemicals as $i => $rChem) {
        $gChem = $greenhouseChemicals[$i];
        expect($gChem->quantityScaled)->toBe(round($rChem->quantityScaled * 0.6, 4));
    }

    expect($greenhouse->adjustmentsApplied)->toContain(SeasonEngine::ADJ_GREENHOUSE_PESTICIDE_CUT);
});

it('greenhouse does NOT discount non-pesticide inputs (seed, fertiliser stay full)', function () {
    $engine = app(SeasonEngine::class);

    $rainfed = $engine->generate(makeEngineInput('tomato', 1.0, 'rainfed'));
    $greenhouse = $engine->generate(makeEngineInput('tomato', 1.0, 'greenhouse'));

    foreach ($rainfed->inputs as $i => $rInput) {
        if ($rInput->inputType === 'chemical') {
            continue;
        }
        expect($greenhouse->inputs[$i]->quantityScaled)
            ->toBe($rInput->quantityScaled);
    }
});

it('rainfed irrigation flags the dry-season risk in adjustments', function () {
    $engine = app(SeasonEngine::class);
    $output = $engine->generate(makeEngineInput('tomato', 1.0, 'rainfed'));

    expect($output->adjustmentsApplied)->toContain(SeasonEngine::ADJ_RAINFED_RISK);
});

it('drip irrigation flags the water-saving signal in adjustments', function () {
    $engine = app(SeasonEngine::class);
    $output = $engine->generate(makeEngineInput('tomato', 1.0, 'drip'));

    expect($output->adjustmentsApplied)->toContain(SeasonEngine::ADJ_DRIP_WATER_SAVING);
});

it('cost estimate equals sum of per-input cost_estimate_kes', function () {
    $engine = app(SeasonEngine::class);
    $output = $engine->generate(makeEngineInput('tomato', 2.0, 'rainfed'));

    $manualSum = 0.0;
    foreach ($output->inputs as $i) {
        if ($i->costEstimateKes !== null) {
            $manualSum += $i->costEstimateKes;
        }
    }

    expect($output->costEstimateTotalKes)->toBe(round($manualSum, 2));
});

it('greenhouse cost is lower than rainfed cost (pesticide savings)', function () {
    $engine = app(SeasonEngine::class);

    $rainfedCost = $engine->generate(makeEngineInput('tomato', 1.0, 'rainfed'))->costEstimateTotalKes;
    $greenhouseCost = $engine->generate(makeEngineInput('tomato', 1.0, 'greenhouse'))->costEstimateTotalKes;

    expect($greenhouseCost)->toBeLessThan($rainfedCost);
});

it('engine is pure — never writes to DB', function () {
    // Crude but effective: count rows before + after a generate(), they should match.
    $engine = app(SeasonEngine::class);

    $beforeActivities = SeasonActivity::withoutGlobalScopes()->count();
    $beforeInputs = InputListItem::withoutGlobalScopes()->count();

    $engine->generate(makeEngineInput('tomato', 1.0));

    expect(SeasonActivity::withoutGlobalScopes()->count())->toBe($beforeActivities)
        ->and(InputListItem::withoutGlobalScopes()->count())->toBe($beforeInputs);
});

it('inputFromSeason helper builds a valid DTO from a Season + crop', function () {
    $crop = Crop::factory()->tomato()->create();
    $tenant = Tenant::factory()->create();
    $season = Season::factory()->create([
        'tenant_id' => $tenant->id,
        'crop_id' => $crop->id,
        'acreage' => 1.5,
        'planting_date' => '2026-06-15',
        'irrigation_type' => 'drip',
    ]);
    $season->setRelation('crop', $crop);

    $input = SeasonEngine::inputFromSeason($season);

    expect($input->cropSlug)->toBe('tomato')
        ->and((float) $input->acreage)->toBe(1.5)
        ->and($input->irrigationType)->toBe('drip')
        ->and($input->plantingDate->toDateString())->toBe('2026-06-15');
});
