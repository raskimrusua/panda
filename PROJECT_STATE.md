# Panda — PROJECT_STATE

**Last updated:** 2026-05-07
**Phase:** P1 (ready to start)
**Status:** Pre-build complete. Pipeline kickoff pending.

---

## CURRENT STATUS

| Layer | State |
|---|---|
| **Spec** | JAICA FarmTrack doc (`~/Downloads/JAICA_FarmTrack_Build_Documentation.docx`) — read, adapted to Laravel + standalone (Panda) shape |
| **Plan** | `~/.claude/plans/piped-knitting-blum.md` — 9 phases, ~12–14 weeks engineering + parallel content authoring |
| **Governance** | `docs/GOVERNANCE_REPORT_2026_05_07.md` — combined 7.05/10 (clears 6.5 gate) |
| **Skills** | 14 Laravel skill files in `~/Desktop/uwc-web-co/00-skills/app-build/laravel/` (P0.5 complete) |
| **Backend** | Not yet scaffolded — P1 starts here |
| **Frontend (PWA)** | Not yet scaffolded — P5 |
| **Marketing site** | Not yet scaffolded — P6 |
| **Repo** | **`raskimrusua/panda`** (private, GH) — initial commit pushed 2026-05-07 |
| **Domain** | `panda.farm` placeholder — confirm during P1 |

---

## PHASE STATUS

| Phase | Description | Status | Owner | ETA |
|---|---|---|---|---|
| P0 | Governance feasibility report | ✅ Done (7.05/10) | UWC governance | 2026-05-07 |
| P0.5 | Laravel skill bootstrap (14 files) | ✅ Done | Claude (this session) | 2026-05-07 |
| P1 | Laravel backend skeleton + content + monitoring + agronomist editor | 🟡 In progress (scaffolded PR #1, Crop+ContentLoader PR #2, multitenancy+auth+Season PR #3; remaining: Filament admin + Sentry wiring + health endpoint) | Pipeline | 2 weeks from kickoff |
| P2 | Season Engine service | ⏳ Pending P1 | Pipeline | +1.2 weeks |
| P3 | Season + Cost + Harvest API | ⏳ Pending P2 | Pipeline | +1.2 weeks |
| P4 | Disease detection (mock) + dealer directory + market prices | ⏳ Pending P3 | Pipeline | +1.5 weeks |
| P5 | React PWA + in-product help + SW translation | ⏳ Pending P4 | Pipeline | +2 weeks |
| P6 | Astro marketing site | ⏳ Pending P5 | Pipeline | +3 days |
| P7 | Content authoring (17 crops × full timeline + diseases) | ⏳ Parallel from P1 | Agronomist (TBH) | 4–8 weeks |
| P8 | Pilot setup + runbooks | ⏳ Pending P5 | Pipeline + Joshua | 1 week |

---

## DEFERRED DECISIONS (will adjust as project goes — per user direction 2026-05-07)

| Item | Status | When it bites |
|---|---|---|
| Agronomist named (paid freelance, KALRO-credentialed) | **✅ Silas** (named 2026-05-11; last name + email + KALRO doc still TBC) | P7 content authoring start |
| Native Swahili translator | Not named | P7 content authoring start (also P5 for UI strings) |
| Pricing tier model (Free / Plus / Pro at KES X / Y) | Not decided | P5 upgrade-modal design |
| Crop.health Kindwise account + monthly cost ceiling | Not signed up | P5 (mocked through P4) |
| Image data retention period | Working assumption: 24 months | P4 disease detection persistence |
| Disease photos for training data — opt-in/opt-out | Working assumption: opt-in default | P4 consent UI |
| Image consent UI text + DPA 2019 wording | TBD | P5 (review with lawyer alongside Shira's ToS work) |
| Domain — `panda.shira.farm` vs alternatives | Working assumption: `panda.shira.farm` | P6 marketing launch |
| Hetzner provisioning timing | **✅ Now** (no longer deferred to P8) — provision in parallel with build, gives PWA a real endpoint to point at | Loops back to "deploy to live API" task |
| GitHub repo — public or private | **✅ Private** (locked 2026-05-11) | — |
| Marketing framing tone — bold vs reserved | TBD | P6 |
| Brand identity (logo, colours, font) | TBD | P5/P6 — not blocking |

---

## OUTSIDE-PANDA DEPENDENCIES (not this project's job, but listed for awareness)

- **Shira PWA out of maintenance mode** — `app.shira.farm` currently shows maintenance page. Separate sub-project. Not blocking Panda since Panda is standalone.
- **Hetzner CX22 capacity** — adding Panda's PHP-FPM + Horizon containers adds ~+200 MB RAM. Currently fine; reassess at 100+ active farmers.
- **Cloudflare Pages** — new project for Panda PWA + marketing. Joshkim04 account.
- **DPA 2019 ToS / consent gate** — Shira-wide work pending lawyer review. Panda inherits the eventual policies.

---

## NEXT ACTIONS — to kick off P1

**For Joshua (one-time provisioning, ~1 hour):**
Follow `docs/PROVISIONING_CHECKLIST.md` end-to-end. Resolved decisions (2026-05-07 evening):
- Disk location: **sibling at `~/Desktop/panda/`** ✅ (this directory)
- GitHub: **monorepo `raskimrusua/panda` (private)** — checklist §1
- Domain: **`panda.shira.farm`** subdomain (no new domain registration) — checklist §3
- Hetzner: **shared CX22 with Shira** (new docker services, new Postgres DB, new nginx vhost) — checklist §5
- CF account: **joshkim04** (same as Shira) — checklist §2
- Deployment style: **manual `npm run deploy`** (mirrors Shira's Pattern C, no auto-deploy from GH push)

**For the pipeline (UWC) — to invoke (after provisioning checklist done):**
1. New Claude Code session in `~/Desktop/panda/`
2. Paste `docs/MASTER_PROMPT.md` as the first message
3. Invoke `/new-app panda` — pipeline scaffolds via `skill-laravel-project-bootstrap`

The pipeline reads `~/Desktop/panda/CLAUDE.md` for architecture rules + the 14 Laravel skill files at `~/Desktop/uwc-web-co/00-skills/app-build/laravel/`. It writes code into `api/`, `pwa/`, `marketing/` subdirs. It does **not** touch CF resources, DNS, SSL, or Hetzner — those are Joshua's via the provisioning checklist.
4. First commit produces `api/` directory with Laravel 11 + Docker + Pest + Filament + Horizon + Sanctum + Sentry
5. Second commit: first model (Crop) + migration + factory + observer + Resource + Controller + tests
6. Third commit: tomato.json content file + content loader service + Redis cache integration
7. Fourth commit: Filament agronomist editor + ContentDraft model + sign-off workflow
8. Fifth commit: monitoring (Sentry + Horizon dashboard + alerts) + health check endpoint
9. P1 complete — re-run governance to confirm score holds, then P2 starts

---

## COMPLETED DELIVERABLES

| Date | Item |
|---|---|
| 2026-05-07 | JAICA spec read + scoped to standalone Panda product |
| 2026-05-07 | Plan (`~/.claude/plans/piped-knitting-blum.md`) authored, governance-amended, Panda-rebranded |
| 2026-05-07 | Governance report v1.0 → v1.1 → v1.2 (combined 7.05/10) |
| 2026-05-07 | 14 Laravel skill files authored in UWC skill portfolio |
| 2026-05-07 | Panda project skeleton (`~/Desktop/panda/{README,CLAUDE,PROJECT_STATE,docs/MASTER_PROMPT}.md`) |
| 2026-05-07 | GH repo `raskimrusua/panda` created (private), initial commit pushed: docs + CI workflow + .gitignore + bootstrap script |
| 2026-05-07 | `bootstrap-laravel.sh` written — runs `composer create-project` + UWC standard packages + Pest/PHPStan/Pint config + Docker scaffold. Run after PHP 8.3 + Composer install completes. |
| 2026-05-08 | **PHP 8.3.31 + Composer 2.9.7 installed via brew** (compiled from source on Intel Mac, ~2 hr). Laravel 11.51 scaffolded in `api/` via bootstrap script. 114 packages installed total (12 main UWC + 4 dev + Laravel transitive). Filament + Horizon + Sentry vendor:publish run. UWC config files applied (pint.json + phpstan.neon + .env.example + config/panda.php + config/filesystems.php R2/S3 toggle + Dockerfile + docker-compose.yml). Pest 2/2 passing, Pint --test pass, PHPStan level 6 no errors. Two security adjustments: `firebase/php-jwt` removed (PKSA-y2cr-5h3j-g3ys advisory; not needed for Panda's standalone Sanctum auth), PHPUnit bumped from `^11.0.1` to `^12.0` to satisfy Pest 4. Pushed to feature branch `add-governance-v1-4-second-opinion` (Joshua's parallel governance v1.4 commit landed there too). PR #1 opened — both governance v1.4 + Laravel scaffold reviewed together. CI green: api-lint pass, api-test pass. **Merged to main (a7a03a4) on 2026-05-08.** |
| 2026-05-08 | **UWC Laravel skill drift fixed** (commit `dd94b89` on `upstate-web-co/uwc-ops` main): skill-laravel-pest-test bumped Pest 3 → Pest 4 + PHPUnit 12; skill-laravel-project-bootstrap made firebase/php-jwt conditional with PKSA-y2cr-5h3j-g3ys note; README + version bumps to v1.1. All 14 Laravel skill files + index README committed (15 files total — first time landing in repo). |
| 2026-05-08 | **PR #2 — first model (Crop) on `feat/p1-crop-model`**: 25 files, +1476 lines. Crop model (ULID + soft-delete + activity log + 2 scopes) + migration + 5 named-state factory (tomato/kale/cabbage/bulb-onion/french-beans). API surface: CropResource (single, shared catalogue) + IndexCropRequest (allowlisted filters: category/harvest_type/phase/active_only/q/per_page) + CropController (read-only index + slug-bound show) + routes/api.php. Content system: crop.schema.json (full JAICA spec) + tomato.json (12 timeline activities + 8 inputs + Tylka F1 / Cal-J varieties + EN/SW bilingual) + ContentLoader service (validates against schema, caches in Redis, ~1ms p99 reads) + `crops:content:reload` artisan command. Tests: 38/38 passing, 136 assertions across CropModelTest (9), CropApiTest (14), ContentLoaderTest (9), ReloadContentCommandTest (4) + 2 Laravel defaults. Pint pass, PHPStan level 6 no errors. **Coverage gate reinstated at --min=70** (Laravel boilerplate excluded via phpunit.xml). Activity log migrations published. install:api ran (Sanctum scaffold). |
| 2026-05-08 | **CI on PR #2 caught a real Postgres-vs-SQLite drift** (commit `a7ae69c`): Spatie ActivityLog's published migration uses `nullableMorphs()` → bigint subject_id; Crop's ULID PK insert fails with `SQLSTATE[22P02]: invalid input syntax for type bigint`. SQLite is typeless, accepted silently (local pest passed). Fix: `nullableMorphs()` → `nullableUlidMorphs()`. CI green after fix. **Lesson captured into UWC `skill-laravel-eloquent-model` v1.1** (Edge Case + Changelog), shipped via UWC PR #4 (commit `a9cb094`). General principle now in skill: "always run CI on Postgres before merging any PR that adds models with vendor packages." |
| 2026-05-09 | **PR #2 merged → main** (`e0a2cf2`). UWC PR #4 (skill v1.1 lesson) merged → upstate-web-co/uwc-ops main. |
| 2026-05-09 | **PR #3 — multitenancy + auth + Season on `feat/p3-tenant-multitenancy`** ([panda#3](https://github.com/raskimrusua/panda/pull/3)): ULID conversion of users + personal_access_tokens migrations (greenfield-edit, ulidMorphs lesson re-applied); Tenant model extending Spatie `SpatieTenant` + Kenyan-county TenantFactory + customised `config/multitenancy.php` for single-DB row mode (tenant_finder = UserTenantFinder; switch_tenant_tasks = PrefixCacheTask only); BelongsToTenant trait (global scope + creating event auto-attach); UserTenantFinder + SetTenantFromUser middleware (registered in priority list BEFORE SubstituteBindings — critical: route-model-binding fires before custom middleware otherwise, leaking foreign tenants as 200 instead of 404); AuthController (register transactional Tenant+User+token, login, logout, me) + Register/Login FormRequests; UserResource + TenantResource. **First tenant-scoped model — Season** (status enum, irrigation enum, engine_metadata JSON, client_id offline-sync key, tenant-scoped unique constraint) + factory (5 named states) + SeasonController (apiResource) + SeasonListResource + SeasonDetailResource + StoreSeason/UpdateSeason FormRequests. Tests: 75 total, all green locally — TenantTest (7), UserTenantFinderTest (3), AuthApiTest (8), SeasonModelTest (7), SeasonApiTest (12 inc. **5 mandatory cross-tenant isolation tests** — list/show/update/destroy/store-with-hijack-payload all 404 or auto-coerce). Pint + PHPStan level 6 clean (3 ignore patterns added: Pest expectation chain template, Carbon date cast through resources). **CI coverage gate raised from 70% → 75%.** **CI status: BLOCKED** — both jobs (api-lint + api-test) failing at runner-pickup phase (6–13s, zero steps execute, runner_id=0). Workflow YAML valid, same file passed for PR #2 two days earlier. Pattern matches GitHub-hosted runner pool issue (likely transient OR account quota). Needs Joshua to check raskimrusua Actions billing/usage page in browser. Code itself verified green locally; PR is otherwise mergeable. |

---

## KNOWN ISSUES / RISKS

- **No engineering blocker for P1 kickoff** — confirmed by governance v1.2.
- **Content authoring is the long pole** — 17 crops × ~6 hours agronomist time + ~3 hours translation each = ~150 person-hours. Realistic: 6–10 weeks part-time. Joshua to source contractor before P7 starts.
- **Disease AI cost unknown until pilot** — at 200 farmers × estimated 5 scans/month × KES 3 ≈ KES 3,000/month. Acceptable. Cost guard envoirned in `CROP_HEALTH_MAX_MONTHLY_KES`.
- **Distribution risk** — standalone Panda has no built-in farmer pipeline (would have inherited Shira's farmers as a Shira module). Need explicit go-to-market in P8: county extension officers, KALRO partnerships, paid acquisition.

---

## Next up — PR #4 (P1 close-out) — confirmed direction (2026-05-11)

User picked: **finish P1 first** (Filament + Sentry + health), then Hetzner provisioning in a focused session, then P2 Season Engine. Standalone Sanctum stays — no JWT sharing with Shira.

Branch from `main` (not `feat/p3-tenant-multitenancy`) so PR #4 is independent of PR #3's CI hold. Filament panel doesn't touch the User changes in PR #3, so no merge conflict.

### PR #4 scope

1. **Filament admin panel**
   - `php artisan filament:install --panels` to create `app/Providers/Filament/AdminPanelProvider.php`
   - Panel mounted at `/admin`, gated by `User::canAccessPanel()` returning `is_superuser` (new boolean column on users — admin-only, default false; note this is one more migration to land before Filament is usable)
   - **CropResource** — Form (slug readonly + name_en/sw + category + harvest_type + image_url + is_active + phase_added) + Table (with status badge + active filter). Ops can edit Crop catalogue rows, but the JSON content file is the source of truth — Crop rows mirror metadata only.
   - **ContentReview** model (new) — `crop_slug`, `submitted_by` (FK users), `reviewer_id` nullable FK, `status` enum (`draft|submitted|approved|changes_requested`), `reviewer_notes` text, timestamps. Soft-delete + activity log. Migration + factory + 3-4 model tests.
   - **ContentReviewResource** — Filament form + table for Silas to submit + ops to approve. Approve action triggers a placeholder `ExportContentJob` (artisan command stub) — actual content-export-to-git wiring deferred to a later PR.

2. **Sentry wiring**
   - `php artisan sentry:publish --dsn=https://example@sentry.io/0` (placeholder; user provides real DSN later via `.env`)
   - Add Sentry handler to `bootstrap/app.php` exceptions block per skill-laravel-sentry.md (if it exists; otherwise per Sentry-Laravel docs)
   - `routes/web.php` smoke-test route `/sentry-smoke-test` (superuser-only) that throws an unhandled exception to verify wiring
   - Document in README how to set the DSN

3. **Health endpoint**
   - `GET /api/v1/health/` (public, no auth — needed for uptime monitoring)
   - Service: `App\Services\HealthCheck` returning `['status' => 'ok'|'degraded', 'checks' => ['db' => '…', 'redis' => '…', 'queue' => '…', 'crop_health' => 'skipped'], 'time' => …]`
   - DB check: `DB::connection()->getPdo()->query('SELECT 1')`
   - Redis check: `Redis::ping()`
   - Queue check: count of failed_jobs in last hour (warn if > 5)
   - Crop.health: `'skipped'` until P5 wires the real client (mock through P4)
   - Returns 200 if all `ok`; 503 if any check fails (so uptime monitors fire)
   - 4-5 Pest tests (each check, all-good, one-broken)

4. **Coverage gate** climbs to 78% (from 75%) since Filament + ContentReview + HealthCheck add a lot of well-tested code.

5. **CI infra**: PR #4 needs the runner-pickup issue resolved before it can merge. Joshua to check `https://github.com/settings/billing/summary` → Actions usage. If quota's the problem, switching `raskimrusua/panda` to public restores free unlimited (acceptable per "let it stay private for now" only if quota isn't blocking — otherwise revisit).

### Deferred to later PR (don't sneak in)
- Internal `/api/v1/internal/farms/sync/` (only matters if Shira ever pushes to Panda; standalone product, low priority)
- Real Sentry alerts wiring (email + SMS) — needs DSN + Sentry project setup first
- Filament agronomist editor for the JSON content files themselves (full structured form per crop) — large surface; better as its own PR after the basic Filament + ContentReview foundation is in
- Hetzner provisioning + nginx vhost + DNS + Origin Cert — separate session per user direction

P2 (Season Engine service) follows PR #4. P2 will turn `Season::created` into a generated activity timeline + input list — pure-class engine, observer fires on creation, all writes inside one `DB::transaction()`.

### Lessons captured during PR #3 (worth eventually backporting to UWC skills)

1. **Spatie multitenancy + custom middleware ordering** — `tenant_finder`'s `findForRequest` runs in `MultitenancyServiceProvider::packageBooted()`, BEFORE auth middleware fires. Standalone Sanctum guards that depend on a hydrated request user will get null on that pass. Solution applied: keep the finder for compatibility (returns null pre-auth), and add a `SetTenantFromUser` middleware that runs AFTER `auth:sanctum`. **Critical**: this middleware must be inserted into the priority list BEFORE `SubstituteBindings::class`, otherwise route-model-binding queries fire without a current tenant set and cross-tenant URLs leak as 200 instead of 404. Add to `skill-laravel-multitenancy` v1.1 Edge Cases.

2. **Greenfield ULID migration of users table** — Sanctum's published `personal_access_tokens` migration uses `morphs('tokenable')` (bigint). For ULID user PKs, edit in place (greenfield) to `ulidMorphs('tokenable')`. Same lesson as PR #2's `nullableMorphs` on activity_log; reinforces "always run CI on Postgres" rule.

3. **`withoutGlobalScopes()` removes SoftDeletingScope too** — using it to test soft-delete behaviour is a footgun. Use `Model::find($id)` (no current tenant → tenant scope no-op) and `Model::withTrashed()->find($id)` instead.

4. **PHPStan + Pest expectation chains** — `$x->and(...)` triggers "unable to resolve template type TAndValue". Add a global `ignoreErrors` pattern scoped to `tests/*` or break chains into separate `expect()` calls.

5. **Larastan + Eloquent date casts** — Larastan can't infer `Carbon` from `'date'` cast through resource accessors; calling `->toDateString()` errors as "Cannot call method on string". Either add `@property \Illuminate\Support\Carbon $field` docblocks on the model, or globally ignore the message. Chose ignore-pattern for now.
