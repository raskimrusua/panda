# CONVENTIONS.md — Panda
**One page. Paste into any session where naming or formatting decisions matter.**

---

## PHP / Laravel

```php
// Models: PascalCase singular, in app/Models/
class Crop extends Model { ... }
class Season extends Model { ... }
class CostRecord extends Model { ... }

// Controllers: PascalCase, Controller suffix, in app/Http/Controllers/Api/V1/
class SeasonController extends Controller { ... }
class CropController extends Controller { ... }

// Form Requests: PascalCase, Request suffix, in app/Http/Requests/
class StoreSeasonRequest extends FormRequest { ... }
class UpdateCostRequest extends FormRequest { ... }

// API Resources: PascalCase, Resource suffix, in app/Http/Resources/
class SeasonResource extends JsonResource { ... }
class SeasonListResource extends SeasonResource { ... }

// Actions: PascalCase, verb-noun, in app/Actions/
class CalculateSeasonProfit { ... }
class DeductFeedInventory { ... }

// Observers: Model name + Observer suffix, in app/Observers/
class SeasonObserver { ... }
class CostRecordObserver { ... }

// Rules: PascalCase, in app/Rules/
class KenyanPhoneNumber implements ValidationRule { ... }
class FutureDate implements ValidationRule { ... }

// Migrations: Laravel auto-names, always in same PR as model
// NEVER split migration from model — Rule #15

// Jobs: PascalCase, in app/Jobs/
class RefreshCropAnalytics implements ShouldQueue { ... }
```

---

## KES CURRENCY HANDLING

```php
// ALWAYS use brick/money — NEVER float, NEVER native Decimal
use Brick\Money\Money;

$amount = Money::of($kes, 'KES');
// Rule #3 — mandatory for ANY monetary field
```

---

## MULTI-TENANCY

```php
// Every tenant-scoped model has tenant_id FK
// Scope via spatie/laravel-multitenancy
// Route group: auth:sanctum + tenant middleware
// NEVER return 403 for cross-tenant — return 404 (Rule #1, #13)
```

---

## API RESPONSE SHAPES

```json
// List — always through a ListResource
{ "data": [...], "meta": { "current_page": 1, "total": 42 } }

// Detail — through a DetailResource
{ "data": { "id": 1, "name": "Tomato Season Q1", ... } }

// Error — Laravel standard
{ "message": "Validation failed", "errors": { "name": ["The name field is required."] } }
```

---

## BILINGUAL (EN/SW) — Rule L3

```typescript
// Frontend: EVERY user-facing string through t('key')
t('seasons.create.title')    // NOT "Create Season" hardcoded
t('crops.tomato.name')       // NOT "Tomato" hardcoded

// Locale files: frontend/src/locales/{en,sw}/*.json
// CI: /lint:i18n catches hardcoded English strings
```

---

## CONTENT SYSTEM — Rule L2

```
// Crops + diseases live as JSON in resources/content/
resources/content/crops/tomato.json
resources/content/diseases/early_blight.json

// Loaded into Redis at startup; cache-bust via artisan
php artisan crops:content:reload
php artisan crops:content:export    // regenerates JSON + commits
```

---

## DISEASE AI — Rule L1

```php
// Provider gated via env; cost ceiling enforced
DISEASE_AI_PROVIDER={mock,crop_health,tflite}
CROP_HEALTH_MAX_MONTHLY_KES=500
// Default: mock in P1-P4. Falls back to mock if ceiling exceeded.
```

---

## GIT COMMIT FORMAT

```
[P1] Scaffold Laravel backend + Crop model + multitenancy
[P3] Add Season Engine service + Cost/Harvest endpoints
[P5] React PWA scaffold + auth + season list
[BUGFIX] Fix ActivityLog flaky assertions — add orderBy('id')
[DPA] Add consent gate middleware + UserConsent model
[I18N] Add Swahili translations for season flow
```

---

## FILE STRUCTURE RULES

```
api/
├── app/
│   ├── Actions/         ← Business logic (one class per action)
│   ├── Models/          ← Eloquent models
│   ├── Http/
│   │   ├── Controllers/Api/V1/  ← API controllers
│   │   ├── Requests/            ← Form Requests (NEVER inline validate)
│   │   ├── Resources/           ← API Resources (List + Detail pairs)
│   │   └── Middleware/          ← Custom middleware
│   ├── Observers/       ← Model observers (single-model side effects)
│   ├── Events/          ← Cross-cutting events
│   ├── Listeners/       ← Event listeners (jobs dispatched here)
│   ├── Rules/           ← Custom validation rules
│   ├── Jobs/            ← Queued jobs (Horizon)
│   └── Services/        ← Third-party integrations
├── database/migrations/ ← Always in same PR as model
├── resources/content/   ← Crop + disease JSON library
└── tests/
    ├── Feature/         ← Pest feature tests
    └── Unit/            ← Pest unit tests
```

---

## TEST PATTERNS (Pest)

```php
// Every feature test:
uses(RefreshDatabase::class);

// External boundaries faked:
Bus::fake();       // jobs
Storage::fake();   // file storage
Http::fake();      // external APIs

// Coverage gate: 70% minimum per PR (Rule #20)
// Always run before commit:
// ./vendor/bin/pest --tb=short -q
// ./vendor/bin/pint --test
// ./vendor/bin/phpstan analyse
```

---

## DPA 2019 CONSENT — Rule L5

```php
// Registration MUST include terms_accepted + privacy_accepted
// UserConsent model: append-only (updating observer throws)
// Stale users get 409 + code: TERMS_VERSION_OUTDATED
// Reconsent at /accept-terms (outside AppShell to avoid loops)
// Advisory disclaimers ride in API payloads for disease/price endpoints
```
