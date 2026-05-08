# CLAUDE.md — Panda

**Architecture rules, conventions, anti-patterns, and project context.**
**Read this file at the start of EVERY session. Do not deviate from these rules.**

This file is the project-specific binding for Claude Code sessions building Panda. It's the Panda counterpart to Shira's `~/Desktop/farmcore/CLAUDE.md` — same shape, Laravel-flavoured.

---

## SESSION STARTUP PROTOCOL — Do this FIRST, every session

1. **Read these files** (in order):
   a. This file (`CLAUDE.md`) — architecture rules
   b. `PROJECT_STATE.md` — current status, what's done, what's next
   c. `docs/MASTER_PROMPT.md` — the JAICA-derived master context (paste into any new Claude Code session as the seed prompt)

2. **Check git state:**
   ```
   git log --oneline -5
   git status
   ```

3. **Read relevant Laravel skill files** (from `~/Desktop/uwc-web-co/00-skills/app-build/laravel/`):
   - Always: `README.md` (the index)
   - Building a model: `skill-laravel-eloquent-model.md` + `skill-laravel-multitenancy.md`
   - Building an endpoint: `skill-laravel-form-request.md` + `skill-laravel-resource.md` + `skill-laravel-controller.md`
   - Adding side-effects: `skill-laravel-observer.md` (single-model) or `skill-laravel-event-listener.md` (cross-cutting)
   - Money: `skill-laravel-money-decimal.md` (mandatory for any monetary field)
   - Storage: `skill-laravel-storage-toggle.md`
   - Tests: `skill-laravel-pest-test.md`

4. **Run tests** before starting work (once Laravel project is bootstrapped):
   ```
   ./vendor/bin/pest --tb=short -q
   ```

5. **Pick ONE deliverable** from `PROJECT_STATE.md` queue.

---

## STACK (exact versions — code against these)

```
Backend:
  PHP             8.3
  Laravel         11.x
  Postgres        16
  Redis           7
  Horizon         5.30+
  Filament        3.2+
  Sanctum         4.0+
  Pest            3.x
  brick/money     0.10+
  spatie/laravel-multitenancy  4.0+
  firebase/php-jwt 6.10+

Frontend (PWA — own app, not shared with Shira):
  Node            20.x
  React           19.x
  TypeScript      5.9.x (strict mode ON)
  Vite            7.x
  TanStack Query  5.x
  react-hook-form 7.x
  zod             3.x
  Tailwind CSS    4.x
  shadcn/ui       (new-york variant)
  axios           1.x

Marketing (Astro):
  Astro           5.x (SSR on Cloudflare Workers, React islands for interactivity)
  Tailwind CSS    4.x
  Cloudflare Pages (deployment)

Hosting:
  Hetzner CX22 — shared box with Shira initially; separate Docker compose stack
  Cloudflare Pages — marketing site + PWA
  Cloudflare R2 — object storage (default; S3 toggle per skill-laravel-storage-toggle)
```

---

## PROJECT OVERVIEW

**Panda** — a Progressive Web App for Kenyan smallholder horticulture farmers.

**Origin:** Adapted from JAICA FarmTrack spec (`~/Downloads/JAICA_FarmTrack_Build_Documentation.docx`). Marketing framing: **"Inspired by JICA SHEP PLUS"** (no formal partnership at launch).

**Standalone, not a Shira module.** Own auth, own users, own data, own URL.

**17 crops Phase 1:** Tomato, Kale, Cabbage, Bulb Onion, French Beans, Capsicum, Chili, Eggplant, Potato, Watermelon, Amaranthus, Black Nightshade, Cowpea Leaves, Avocado, Banana, Mango, Passion Fruit. Bilingual (EN + SW).

**Primary market:** Kenya (KES, M-Pesa, Swahili + English).
**Pilot counties (Phase 1):** Meru + Kirinyaga, 200 farmers.
**Pricing:** TBD (decision deferred — see `PROJECT_STATE.md`).

**Pricing tiers (placeholder, to be decided):** Free / Plus (KES TBD/mo) / Pro (KES TBD/mo).

---

## LARAVEL ARCHITECTURE RULES — Numbered, reference by number

**These mirror Shira's 42 Django rules in spirit.** Where the Laravel and Django rule align word-for-word, the rule number matches. Where Laravel needs its own rule, it's L-prefixed.

### 1. Tenant Isolation — The Root Rule
EVERY business data model MUST have `tenant_id` ULID FK to `tenants`. Use the `BelongsToTenant` trait (skill-laravel-multitenancy §5). Cross-tenant access returns **404 (not 403)**.

### 2. Auth — Sanctum, standalone
Panda issues its own JWTs via Laravel Sanctum. **Do NOT** use the shared-JWT pattern from skill-laravel-sanctum-shared-jwt; that skill exists for *future* projects where shared identity is the design. Panda is standalone.

### 3. Money — ALWAYS brick/money, NEVER float
NEVER use float for any monetary value. ALL monetary fields use `MoneyCast` Eloquent cast. See `skill-laravel-money-decimal.md`. PHPStan rule bans raw `floatval` on money attributes.

### 4. Financial mutations — DB::transaction()
EVERY write touching financial state (`CostEntry`, `HarvestLog`, M-Pesa callbacks) MUST be wrapped in `DB::transaction()`. M-Pesa callbacks always return 200 to Safaricom regardless of processing outcome.

### 5. Observers — Single Responsibility
One Observer per model. Observer methods are explicit (`created`, `updated`, `deleted` — never `saved`/`saving` unless deliberate). See `skill-laravel-observer.md`.

### 6. Service Layer — Where Business Logic Lives
Business logic goes in `app/Services/<Domain>/<Name>Service.php`. NOT in models, NOT in controllers, NOT in observers. Observers call services. Controllers call services. Models are pure data.

### 7. Snapshot updates — `update_fields` equivalent
For Eloquent partial updates, use `$model->update(['field' => $value])` not `$model->field = $value; $model->save()` — the former emits a single SQL UPDATE for the field; the latter rewrites the row.

### 8. Alert / Notification routing — through one service
NEVER create notification rows directly. Always via `app/Services/Notifications/NotificationService.php`. Encapsulates SMS truncation, email templating, NotificationLog persistence.

### 9. Educational tips — TipService
NEVER render tip rows directly to UI. Always via `app/Services/Tips/TipService.php`. Respects `show_once_per_days`. EN default, SW if available.

### 10. Horizon — Queue Separation
Tasks route to the correct queue — NEVER use `default` for non-trivial work:
- `engines` — season engine runs (latency-sensitive)
- `analytics` — daily/weekly aggregations (slow batch)
- `notifications` — SMS/email/push delivery
- `integrations` — external APIs (Crop.health, M-Pesa, dealer verify)
- `default` — only trivial logging
See `skill-laravel-horizon-queue.md`.

### 11. Job uniqueness — for engine jobs
Jobs that must not run concurrently per-entity (e.g., `RunSeasonEngineJob` per season) MUST implement `ShouldBeUnique` with `uniqueId()` returning the entity ID and `uniqueFor` set to a sane window.

### 12. API Error Envelope — Laravel default 422 shape
ALL API errors use Laravel's default 422 envelope:
```json
{ "message": "...", "errors": { "field": ["..."] } }
```
This matches the frontend `useApiError` hook expectations from Shira's PWA pattern.

### 13. Tenant-leaking — return 404, not 403
For any farm-scoped resource that exists for a different tenant, return 404. 403 reveals existence. 404 reveals nothing.

### 14. Offline Sync — `client_id` ULID
Every offline-created record MUST have a `client_id` ULID for idempotency. Endpoints use `firstOrCreate(['client_id' => $id], ...)` — same `client_id` submitted twice returns the same row, never duplicates.

### 15. Migrations — paired with model in same PR
Laravel migrations are NOT auto-generated from models — you write both. Always in the same PR. Never split. See `skill-laravel-eloquent-model.md`.

### 16. M-Pesa — STK Push + callback
M-Pesa Daraja STK Push for KES subscriptions. Callbacks: ALWAYS return 200 to Safaricom. Save `CheckoutRequestID` immediately. Use `firstOrCreate` keyed on `CheckoutRequestID` for callback idempotency.

### 17. Validators — centralised, never inline
ALL validation rules live in `app/Rules/`. Never inline closures in `rules()`. See `skill-laravel-form-request.md`.

### 18. Resources — never `toArray()`, never `__all__`
Every API response goes through an API Resource with explicit fields. Paired List + Detail Resources for tenant-scoped CRUD models. See `skill-laravel-resource.md`.

### 19. Analytics models — read-only, written by jobs
Analytics models are NEVER written from controllers or observers. Only by scheduled Horizon jobs. The dashboard API reads only from analytics models.

### 20. Pest — performance + isolation
Tests run with Postgres (not SQLite), `RefreshDatabase` per test, `Bus::fake` / `Storage::fake` / `Http::fake` at every external boundary. Coverage gate: 70% minimum on every PR. See `skill-laravel-pest-test.md`.

### 21. FarmSettings (project equivalent: TenantSettings) — per-tenant configurable
NEVER hardcode business-logic thresholds. Centralised settings model with Redis cache (5-min TTL). Per-tenant configurable via Filament admin.

### 22. Filament admin — role-gated
`/admin` is admin/agronomist-only. nginx + Cloudflare Access IP allowlist for production. Never publicly accessible. See `skill-filament-admin.md`.

### 23. Storage backend — env-toggleable
Object storage backend chosen via `STORAGE_BACKEND={r2,s3}` env var. Application code never names a specific disk. See `skill-laravel-storage-toggle.md`.

### L1. Disease AI — provider gate
`DISEASE_AI_PROVIDER={mock,crop_health,tflite}`. Defaults to `mock` in P1–P4. Cost ceiling enforced via `CROP_HEALTH_MAX_MONTHLY_KES` env var; if exceeded, falls back to mock + raises alert.

### L2. Content system — JSON files, Redis cache, Filament editor
Crops + diseases stored as JSON files in `resources/content/{crops,diseases}/*.json`. Loaded into Redis at startup. Cache-bust via `php artisan crops:content:reload`. Agronomist authors via Filament editor, admin approves, `php artisan crops:content:export` regenerates JSON + commits.

### L3. Bilingual — i18next, no hardcoded strings
Every user-facing string in PWA passes through `t('key')`. New keys land in `frontend/src/locales/{en,sw}/*.json`. CI runs `/lint:i18n` to catch hardcoded English strings in `features/crops/`.

### L4. Image consent + retention
Disease photo uploads require farmer consent flag. Default retention 24 months (env-overridable). Opt-in (default) for use as Phase 2 custom-model training data.

---

## ANTI-PATTERNS — NEVER

| Anti-pattern | Why wrong | Rule |
|---|---|---|
| `(float) $cost` on money attribute | Float drift | #3 |
| Inline validation in controller | Not centralised, can't test | #17 |
| `$request->validate(...)` in controller | Bypass FormRequest | #17 |
| Returning `$model->toArray()` from controller | Leaks every column | #18 |
| `fields = '__all__'` equivalent | Same | #18 |
| `Storage::disk('r2')` hardcoded | Blocks toggle | #23 |
| `auth:sanctum` returning 200 + auth flag | 401 is the contract | #2 |
| 403 instead of 404 for cross-tenant | Leaks existence | #1, #13 |
| Job without ShouldQueue + tries + timeout | Block request, infinite retry | #10 |
| Observer hits external API inline | Block request, fragile | #5 |
| Bulk `->update()` expecting Observer | Doesn't fire for query-builder updates | #5 |
| Disease AI cost not env-capped | Surprise variable cost | #L1 |
| Hardcoded English in PWA | No SW for farmer | #L3 |
| Image upload without consent flag | DPA 2019 risk | #L4 |

---

## PROJECT STRUCTURE (target — created by pipeline during P1)

```
panda/
├── README.md                          ← what is Panda (this file's sibling)
├── CLAUDE.md                          ← THIS file (read every session)
├── PROJECT_STATE.md                   ← current status (update after every milestone)
├── docs/
│   ├── MASTER_PROMPT.md               ← JAICA master context for Claude Code sessions
│   ├── GOVERNANCE_REPORT_2026_05_07.md ← pre-build report
│   └── runbooks/                      ← operational runbooks (P8)
├── api/                               ← Laravel 11 backend (created by P1)
│   ├── app/
│   ├── config/
│   ├── database/migrations/
│   ├── resources/content/             ← JSON content library
│   ├── routes/
│   ├── tests/
│   ├── docker/
│   ├── composer.json
│   └── .env.example
├── pwa/                               ← React PWA (created by P5)
│   ├── src/features/{seasons,disease,inputs,dealers,prices,learn}/
│   ├── src/api/                       ← talks to api.panda.farm
│   └── ...
└── marketing/                         ← Astro site (created by P6)
    ├── src/pages/
    └── ...
```

---

## PIPELINE — How Panda actually gets built

UWC's pipeline drives development from here. Joshua's role: decisions, not coding.

**To kick off P1 (the Laravel backend):**
1. New Claude Code session in `~/Desktop/panda/`
2. Paste `docs/MASTER_PROMPT.md` as the first message (the project context)
3. Invoke `/new-app panda` (the rescoped skill — see `~/Desktop/uwc-web-co/00-skills/app-build/`)
4. Pipeline scaffolds via `skill-laravel-project-bootstrap` → first model → tests
5. Each subsequent phase: paste the master prompt + invoke the next skill

**Outside-Claude actions Joshua does:**
- Decide deferred items as they surface (agronomist hire, SW translator, pricing, image policies)
- Approve PRs that the pipeline opens
- Run governance re-checks weekly during pilot

**Outside-Panda dependencies:**
- Shira PWA out of maintenance mode — separate sub-project, not Panda's responsibility
- Crop.health Kindwise account — needed for P5 (mock works through P4)
- Hetzner Postgres + Redis — already running for Shira; just create new DB + Redis index

---

## COMMIT POLICY (mirrors Shira's)

1. NEVER `git add -A` or `git add .` — always add files explicitly by name
2. NEVER commit: `.env*`, `vendor/`, `node_modules/`, `bootstrap/cache/*`
3. Group commits by deliverable — one coherent feature per commit
4. Commit order: migration → model → factory → observer → service → FormRequest → controller → resource → tests
5. Pre-commit checks:
   - `git diff --cached | grep -iE 'SECRET|PASSWORD|API_KEY|TOKEN'` → must return nothing
   - `./vendor/bin/pest --tb=short -q` → tests must pass
   - `./vendor/bin/pint --test` → code style passes
   - `./vendor/bin/phpstan analyse` → static analysis passes

---

## KNOWLEDGE CUTOFF

Built starting May 2026. Laravel 11.x, PHP 8.3, Filament 3.2+. If Laravel 12 features are needed, verify compatibility before using.

*Documentation initialised: 2026-05-07.*
