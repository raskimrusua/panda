<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CostEntry;
use App\Models\Crop;
use App\Models\DiseaseDetection;
use App\Models\HarvestLog;
use App\Models\Season;
use App\Models\SeasonActivity;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DealerSeeder;
use Database\Seeders\MarketPriceSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * panda:seed-demo — generates a realistic Kenyan smallholder farm dataset for
 * marketing screenshots, end-to-end smoke testing, and Phase-8 pilot demos.
 *
 * Three sizes (mirrors Shira's seed_unified / seed_realistic split):
 *
 *   --months=3   1 active season at week ~10 (mid-vegetative)
 *                Demonstrates the cost-logging cadence + first activities done.
 *
 *   --months=6   2 seasons: 1 currently harvesting, 1 just-planted
 *                Demonstrates the multi-season list + harvest pick logging.
 *
 *   --months=12  3 seasons: 1 closed (full lifecycle), 1 harvesting, 1 planning
 *                Demonstrates everything: lender-grade PDF report, full timeline
 *                completion, multi-pick harvests, multiple cost categories,
 *                disease scans, dealer interactions.
 *
 * Idempotent on the demo tenant slug (`demo-farm`). Re-running upgrades the
 * existing tenant's data; pass --fresh to wipe + reseed.
 */
class SeedDemo extends Command
{
    /** @var string */
    protected $signature = 'panda:seed-demo
        {--months=12 : Demo size — 3, 6, or 12}
        {--fresh : Drop the demo tenant and reseed from scratch}
        {--tenant-name=Demo Farm : Display name for the demo tenant}
        {--user-email=demo@panda.shira.farm : Login email for the demo superuser}
        {--password=demo-panda : Login password (default: demo-panda)}
        {--no-catalog : Skip dealers + market price seeders (faster reruns)}
    ';

    /** @var string */
    protected $description = 'Seed a realistic 3 / 6 / 12-month farm dataset for screenshots, demos, and gap analysis.';

    public function handle(): int
    {
        $months = (int) $this->option('months');
        if (! in_array($months, [3, 6, 12], true)) {
            $this->error('--months must be 3, 6, or 12.');

            return self::FAILURE;
        }

        $tenantSlug = 'demo-farm';
        $email = (string) $this->option('user-email');

        if ($this->option('fresh')) {
            $this->info('Dropping existing demo tenant…');
            Tenant::withoutGlobalScopes()->where('slug', $tenantSlug)->forceDelete();
        }

        if (! $this->option('no-catalog')) {
            $this->seedCatalog();
        }

        DB::transaction(function () use ($months, $tenantSlug, $email): void {
            $tenant = $this->upsertTenant($tenantSlug);
            $user = $this->upsertUser($tenant, $email);
            $tomato = $this->ensureCropTomato();

            $tenant->makeCurrent();
            try {
                $seasons = $this->seasonsFor($months, $tenant, $user, $tomato);
                foreach ($seasons as $season) {
                    $this->dressSeason($season, $user);
                }
                $this->seedDiseaseDetections($tenant, $tomato, $user, $months);
            } finally {
                $tenant->forgetCurrent();
            }

            $this->reportSummary($tenant, $user, $months);
        });

        return self::SUCCESS;
    }

    private function seedCatalog(): void
    {
        $this->info('Seeding shared catalog (dealers + 12mo market prices)…');
        (new DealerSeeder)->run();
        (new MarketPriceSeeder)->run();
    }

    private function upsertTenant(string $slug): Tenant
    {
        return Tenant::withoutGlobalScopes()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => (string) $this->option('tenant-name'),
                'county' => 'Meru',
                'sub_county' => 'Imenti South',
                'ward' => 'Mitunguu',
                'gps_lat' => 0.0489,
                'gps_lng' => 37.6498,
                'settings' => [
                    'language' => 'en',
                    'notification_email' => true,
                    'notification_sms' => false,
                ],
            ],
        );
    }

    private function upsertUser(Tenant $tenant, string $email): User
    {
        $user = User::withoutGlobalScopes()->updateOrCreate(
            ['email' => $email],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Joshua Mukui',
                'password' => Hash::make((string) $this->option('password')),
                'is_superuser' => true,
                'email_verified_at' => now(),
            ],
        );
        // Re-set password each run so the documented default always works.
        $user->forceFill(['password' => Hash::make((string) $this->option('password'))])->save();

        return $user;
    }

    private function ensureCropTomato(): Crop
    {
        return Crop::withoutGlobalScopes()->firstOrCreate(
            ['slug' => 'tomato'],
            [
                'name_en' => 'Tomato',
                'name_sw' => 'Nyanya',
                'category' => 'vegetable',
                'harvest_type' => 'multi',
                'is_active' => true,
                'phase_added' => 1,
            ],
        );
    }

    /**
     * @return array<int, Season>
     */
    private function seasonsFor(int $months, Tenant $tenant, User $user, Crop $tomato): array
    {
        $today = Carbon::today();
        $base = [
            'tenant_id' => $tenant->id,
            'crop_id' => $tomato->id,
            'irrigation_type' => Season::IRRIGATION_DRIP,
        ];

        return match ($months) {
            3 => [
                $this->makeSeason($base + [
                    'planting_date' => $today->copy()->subWeeks(10),
                    'acreage' => 1.0,
                    'status' => Season::STATUS_ACTIVE,
                    'client_id' => (string) Str::ulid(),
                ], $user),
            ],
            6 => [
                $this->makeSeason($base + [
                    'planting_date' => $today->copy()->subWeeks(20),
                    'acreage' => 1.5,
                    'status' => Season::STATUS_HARVESTING,
                    'client_id' => (string) Str::ulid(),
                ], $user),
                $this->makeSeason($base + [
                    'planting_date' => $today->copy()->subWeeks(4),
                    'acreage' => 0.75,
                    'status' => Season::STATUS_ACTIVE,
                    'irrigation_type' => Season::IRRIGATION_RAINFED,
                    'client_id' => (string) Str::ulid(),
                ], $user),
            ],
            12 => [
                $this->makeSeason($base + [
                    'planting_date' => $today->copy()->subWeeks(46),
                    'acreage' => 1.5,
                    'status' => Season::STATUS_COMPLETE,
                    'client_id' => (string) Str::ulid(),
                ], $user),
                $this->makeSeason($base + [
                    'planting_date' => $today->copy()->subWeeks(20),
                    'acreage' => 2.0,
                    'status' => Season::STATUS_HARVESTING,
                    'irrigation_type' => Season::IRRIGATION_GREENHOUSE,
                    'client_id' => (string) Str::ulid(),
                ], $user),
                $this->makeSeason($base + [
                    'planting_date' => $today->copy()->subWeeks(3),
                    'acreage' => 0.5,
                    'status' => Season::STATUS_PLANNING,
                    'irrigation_type' => Season::IRRIGATION_RAINFED,
                    'client_id' => (string) Str::ulid(),
                ], $user),
            ],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $attrs
     */
    private function makeSeason(array $attrs, User $user): Season
    {
        $attrs['planting_date'] = $attrs['planting_date'] instanceof Carbon
            ? $attrs['planting_date']->toDateString()
            : (string) $attrs['planting_date'];

        $season = Season::create($attrs);

        // SeasonObserver auto-runs the engine and creates SeasonActivity +
        // InputListItem rows. Refresh so we see them in this transaction.
        return $season->refresh();
    }

    private function dressSeason(Season $season, User $user): void
    {
        $today = Carbon::today();
        $weeksSincePlanting = (int) $today->diffInWeeks(
            Carbon::parse($season->planting_date),
            absolute: true,
        );

        $this->markActivitiesDone($season, $weeksSincePlanting, $user);
        $this->seedCosts($season, $weeksSincePlanting, $user);
        $this->seedHarvests($season, $weeksSincePlanting, $user);
    }

    private function markActivitiesDone(Season $season, int $weeksElapsed, User $user): void
    {
        $activities = SeasonActivity::where('season_id', $season->id)
            ->orderBy('week_from_planting')
            ->get();

        foreach ($activities as $activity) {
            $week = (int) $activity->week_from_planting;
            $shouldBeDone = $week <= $weeksElapsed - 1; // small grace
            if (! $shouldBeDone) {
                continue;
            }
            $completedAt = Carbon::parse($season->planting_date)
                ->addWeeks(max(0, $week))
                ->addDays(random_int(0, 2))
                ->setTime(7, random_int(0, 59));

            $activity->forceFill([
                'status' => SeasonActivity::STATUS_DONE,
                'completed_at' => $completedAt,
                'completed_by' => $user->id,
                'completion_notes' => null,
            ])->save();
        }
    }

    private function seedCosts(Season $season, int $weeksElapsed, User $user): void
    {
        // Realistic Kenyan smallholder cost timeline: seed at week 0,
        // basal fertiliser at week 1, labour weekly, top-dress fertiliser at
        // week 6, chemical sprays at weeks 6/8/10, transport at every harvest.
        $items = [
            ['week' => 0, 'category' => CostEntry::CATEGORY_SEED, 'description' => 'Tomato hybrid seed (Anna F1) — 50g', 'amount' => 1800, 'supplier' => 'Elgon Kenya - Meru'],
            ['week' => 1, 'category' => CostEntry::CATEGORY_FERTILISER, 'description' => 'DAP basal fertiliser', 'amount' => 5400, 'supplier' => 'Elgon Kenya - Meru'],
            ['week' => 1, 'category' => CostEntry::CATEGORY_LABOUR, 'description' => 'Land prep + seedbed (3 casuals × 1 day)', 'amount' => 1500, 'supplier' => null],
            ['week' => 3, 'category' => CostEntry::CATEGORY_LABOUR, 'description' => 'Transplanting (3 casuals × 1 day)', 'amount' => 1500, 'supplier' => null],
            ['week' => 4, 'category' => CostEntry::CATEGORY_FERTILISER, 'description' => 'CAN top-dress 1', 'amount' => 3200, 'supplier' => 'ETG Inputs - Meru Town'],
            ['week' => 6, 'category' => CostEntry::CATEGORY_CHEMICAL, 'description' => 'Ridomil Gold (early blight prevention)', 'amount' => 2100, 'supplier' => 'Twiga Chemicals - Meru'],
            ['week' => 6, 'category' => CostEntry::CATEGORY_LABOUR, 'description' => 'First weeding (4 casuals × 1 day)', 'amount' => 2000, 'supplier' => null],
            ['week' => 8, 'category' => CostEntry::CATEGORY_FERTILISER, 'description' => 'CAN top-dress 2', 'amount' => 3200, 'supplier' => 'ETG Inputs - Meru Town'],
            ['week' => 8, 'category' => CostEntry::CATEGORY_CHEMICAL, 'description' => 'Ambush Super (Tuta absoluta)', 'amount' => 1900, 'supplier' => 'Twiga Chemicals - Meru'],
            ['week' => 10, 'category' => CostEntry::CATEGORY_EQUIPMENT, 'description' => 'Bamboo stakes (200 pcs)', 'amount' => 4000, 'supplier' => 'Local supplier'],
            ['week' => 10, 'category' => CostEntry::CATEGORY_LABOUR, 'description' => 'Staking + tying (4 casuals × 1 day)', 'amount' => 2000, 'supplier' => null],
            ['week' => 12, 'category' => CostEntry::CATEGORY_TRANSPORT, 'description' => 'Boda boda — first harvest to Marikiti', 'amount' => 600, 'supplier' => null],
            ['week' => 14, 'category' => CostEntry::CATEGORY_TRANSPORT, 'description' => 'Boda boda — second pick to Marikiti', 'amount' => 600, 'supplier' => null],
            ['week' => 16, 'category' => CostEntry::CATEGORY_LABOUR, 'description' => 'Harvest labour (3 casuals × 1 day)', 'amount' => 1500, 'supplier' => null],
            ['week' => 18, 'category' => CostEntry::CATEGORY_TRANSPORT, 'description' => 'Lorry hire — bulk harvest to Wakulima', 'amount' => 4500, 'supplier' => null],
        ];

        foreach ($items as $item) {
            if ($item['week'] > $weeksElapsed) {
                continue;
            }
            $incurredAt = Carbon::parse($season->planting_date)
                ->addWeeks((int) $item['week'])
                ->addDays(random_int(0, 2));
            $amount = (float) $item['amount'] * (float) $season->acreage;

            CostEntry::firstOrCreate(
                [
                    'season_id' => $season->id,
                    'category' => $item['category'],
                    'description' => $item['description'],
                ],
                [
                    'tenant_id' => $season->tenant_id,
                    'amount_kes' => round($amount, 2),
                    'incurred_at' => $incurredAt,
                    'supplier_name' => $item['supplier'],
                    'logged_by' => $user->id,
                ],
            );
        }
    }

    private function seedHarvests(Season $season, int $weeksElapsed, User $user): void
    {
        // Tomato multi-pick window: weeks 12–22, ~5–7 day gaps. Yield ramps
        // up to peak around weeks 14–16 then tapers. Greenhouse adds ~30%.
        $multiplier = ($season->irrigation_type === Season::IRRIGATION_GREENHOUSE) ? 1.3 : 1.0;
        $picks = [
            ['week' => 12, 'kg' => 80,  'price' => 65, 'sold_pct' => 1.0,  'buyer' => 'Marikiti broker'],
            ['week' => 13, 'kg' => 110, 'price' => 70, 'sold_pct' => 1.0,  'buyer' => 'Marikiti broker'],
            ['week' => 14, 'kg' => 180, 'price' => 60, 'sold_pct' => 0.95, 'buyer' => 'Wakulima Market'],
            ['week' => 15, 'kg' => 200, 'price' => 55, 'sold_pct' => 0.92, 'buyer' => 'Wakulima Market'],
            ['week' => 16, 'kg' => 220, 'price' => 50, 'sold_pct' => 0.90, 'buyer' => 'Wakulima Market'],
            ['week' => 17, 'kg' => 170, 'price' => 55, 'sold_pct' => 0.95, 'buyer' => 'Marikiti broker'],
            ['week' => 18, 'kg' => 140, 'price' => 60, 'sold_pct' => 1.0,  'buyer' => 'Marikiti broker'],
            ['week' => 19, 'kg' => 100, 'price' => 70, 'sold_pct' => 1.0,  'buyer' => 'Marikiti broker'],
            ['week' => 20, 'kg' => 80,  'price' => 75, 'sold_pct' => 1.0,  'buyer' => 'Local school'],
            ['week' => 21, 'kg' => 50,  'price' => 80, 'sold_pct' => 1.0,  'buyer' => 'Local school'],
        ];

        foreach ($picks as $pick) {
            if ($pick['week'] > $weeksElapsed) {
                continue;
            }
            $harvestedAt = Carbon::parse($season->planting_date)
                ->addWeeks((int) $pick['week'])
                ->addDays(random_int(0, 2));
            $kg = $pick['kg'] * (float) $season->acreage * $multiplier;
            $sold = $kg * $pick['sold_pct'];

            HarvestLog::firstOrCreate(
                [
                    'season_id' => $season->id,
                    'harvested_at' => $harvestedAt->toDateString(),
                ],
                [
                    'tenant_id' => $season->tenant_id,
                    'quantity_kg' => round($kg, 2),
                    'sold_quantity_kg' => round($sold, 2),
                    'unit_price_kes' => $pick['price'],
                    'buyer_name' => $pick['buyer'],
                    'notes' => null,
                    'photo_url' => null,
                    'client_id' => (string) Str::ulid(),
                    'logged_by' => $user->id,
                ],
            );
        }
    }

    private function seedDiseaseDetections(Tenant $tenant, Crop $tomato, User $user, int $months): void
    {
        // 1 detection per ~4 months — enough variety for screenshots without
        // making the disease history page noisy.
        $count = max(1, intdiv($months, 4));
        $diagnoses = [
            ['name' => 'Early blight', 'confidence' => 0.87, 'treatments' => ['Apply Ridomil Gold MZ at label rate', 'Remove + burn affected leaves']],
            ['name' => 'Tuta absoluta', 'confidence' => 0.79, 'treatments' => ['Spray Ambush Super every 10 days', 'Install pheromone traps at 2/acre']],
            ['name' => 'Bacterial wilt', 'confidence' => 0.71, 'treatments' => ['Uproot + burn infected plants', 'Avoid replanting tomato in same plot for 4 seasons']],
        ];

        for ($i = 0; $i < $count; $i++) {
            $d = $diagnoses[$i % count($diagnoses)];
            DiseaseDetection::create([
                'tenant_id' => $tenant->id,
                'crop_id' => $tomato->id,
                'image_path' => 'tenants/'.$tenant->id.'/disease/demo-leaf-'.($i + 1).'.jpg',
                'provider' => DiseaseDetection::PROVIDER_MOCK,
                'top_diagnosis' => $d['name'],
                'confidence' => $d['confidence'],
                'engine_response' => ['raw' => ['top_match' => $d['name'], 'confidence' => $d['confidence']]],
                'treatments' => $d['treatments'],
                'opt_in_for_training' => true,
                'captured_at' => now()->subWeeks(random_int(2, $months * 4)),
                'captured_by' => $user->id,
            ]);
        }
    }

    private function reportSummary(Tenant $tenant, User $user, int $months): void
    {
        $seasonCount = Season::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
        $costsCount = CostEntry::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
        $harvestsCount = HarvestLog::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
        $diseaseCount = DiseaseDetection::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();

        $this->newLine();
        $this->info("Demo seed complete ({$months}-month mode)");
        $this->table(
            ['Resource', 'Count'],
            [
                ['Tenant', $tenant->name.' ('.$tenant->slug.')'],
                ['User', $user->email.' / '.$this->option('password')],
                ['Seasons', (string) $seasonCount],
                ['Cost entries', (string) $costsCount],
                ['Harvest logs', (string) $harvestsCount],
                ['Disease detections', (string) $diseaseCount],
            ],
        );
        $this->line('Login at: https://api.panda.shira.farm/admin (Filament panel for superuser)');
        $this->line('Or via PWA: https://app.panda.shira.farm with the credentials above.');
    }
}
