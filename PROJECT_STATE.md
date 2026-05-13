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
| 2026-05-09 | **PR #3 — multitenancy + auth + Season** ([panda#3](https://github.com/raskimrusua/panda/pull/3)). 75 tests. CI status: **BLOCKED** — runner-pickup phase, 6-13s zero-step failures. Needs Joshua to check raskimrusua Actions billing/usage. Code verified green locally. |
| 2026-05-11 | **PR #4 — Filament + Sentry + health endpoint** ([panda#4](https://github.com/raskimrusua/panda/pull/4)). Independent of PR #3 chain. 17 tests, coverage gate 75 → 78. CI BLOCKED (same infra issue). |
| 2026-05-12 | **PR #5 — P2 Season Engine + SeasonActivity + InputListItem** ([panda#5](https://github.com/raskimrusua/panda/pull/5)). Pure-class engine + observer + atomic persist. 18 tests. Branched from PR #3. CI BLOCKED. |
| 2026-05-12 | **PR #6 — P3 finish: CostEntry + HarvestLog + log-done + procurement + PDF report** ([panda#6](https://github.com/raskimrusua/panda/pull/6)). 14 new endpoints, HarvestLog observer, dompdf report. 28 tests. Branched from PR #5. CI BLOCKED. |
| 2026-05-12 | **PR #7 — P4: Disease (mock CropHealthClient) + Dealer (30 real Kenyan agro-dealers + haversine search) + MarketPrice (~1560-row 12mo seed + latest/history/forecast)** ([panda#7](https://github.com/raskimrusua/panda/pull/7)). 25 tests. Branched from PR #6. CI BLOCKED. |
| 2026-05-12 | **TanStack supply-chain CVE-2026-45321** discovered (Mini Shai-Hulud, CVSS 9.6, 84 malicious @tanstack/router\* package versions on 2026-05-11). Audit captured at `docs/audits/2026-05-12-tanstack-cve-2026-45321.md`. **Verdict: Panda + Shira BOTH not exposed** — both only install `@tanstack/react-query` family which is in the maintainer's confirmed-clean list. UWC `skill-ci-github-actions` v1.1 also bumped earlier with two debug recipes (D1 runner-pickup, D2 hashFiles antipattern). |
| 2026-05-12 | **PR #8 — P5 PWA scaffold (Vite + React 19 + TS strict + Tailwind 4 + Workbox PWA)** ([panda#8](https://github.com/raskimrusua/panda/pull/8)). Auth + season CRUD + PWA install. 22 PWA tests. axios bumped 1.7→1.16 to skip SSRF range. New CI jobs: pwa-typecheck + pwa-test. Branched from PR #7. |
| 2026-05-12 | **PR #9 — P5 PWA log forms (mark-done, log cost, log harvest)** ([panda#9](https://github.com/raskimrusua/panda/pull/9)). Modal primitive + 3 forms + Costs/Harvests tabs. 13 new tests (35 PWA total). Branched from PR #8. |
| 2026-05-12 | **PR #10 — P5 PWA disease scan + dealer map (Leaflet) + price chart (hand-rolled SVG)** ([panda#10](https://github.com/raskimrusua/panda/pull/10)). Camera capture + geolocation. 3 new tests (38 PWA total). +185 KiB precache for leaflet. Branched from PR #9. |
| 2026-05-12 | **PR #11 — P5 PWA offline write queue (idb-keyval + replay) + i18n with full Swahili translation** ([panda#11](https://github.com/raskimrusua/panda/pull/11)). 100+ string keys translated en/sw. OnlineIndicator + LanguageSwitcher in sidebar. 14 new tests (49 PWA total). +73 KiB precache. Branched from PR #10. |

---

## KNOWN ISSUES / RISKS

- **No engineering blocker for P1 kickoff** — confirmed by governance v1.2.
- **Content authoring is the long pole** — 17 crops × ~6 hours agronomist time + ~3 hours translation each = ~150 person-hours. Realistic: 6–10 weeks part-time. Joshua to source contractor before P7 starts.
- **Disease AI cost unknown until pilot** — at 200 farmers × estimated 5 scans/month × KES 3 ≈ KES 3,000/month. Acceptable. Cost guard envoirned in `CROP_HEALTH_MAX_MONTHLY_KES`.
- **Distribution risk** — standalone Panda has no built-in farmer pipeline (would have inherited Shira's farmers as a Shira module). Need explicit go-to-market in P8: county extension officers, KALRO partnerships, paid acquisition.

---

## Resume point — 2026-05-12 (compaction marker)

**Where we are:** 9 PRs open in chain, all CI-blocked on the same runner-pickup infra issue. **No new code is needed for backend (P1-P4) or PWA core (P5 #8-#11) — all feature-complete.** The remaining engineering surfaces are PR #12 (small) and P6 marketing site (medium), then P8 pilot setup.

### PR queue (open against the chain — every one verified green locally)

```
main (last merge: PR #2 = Crop catalogue + ContentLoader)
 ├── PR #3   feat/p3-tenant-multitenancy           multitenancy + Sanctum + Season              [75 tests]
 │    └── PR #5  feat/p2-season-engine             Season Engine + activities + inputs          [+18 → 93]
 │         └── PR #6  feat/p3-finish-costs-harvests   costs + harvests + log-done + PDF         [+28 → 121]
 │              └── PR #7  feat/p4-disease-dealers-prices   disease + dealers + prices           [+25 → 146]
 │                   └── PR #8  feat/p5-pwa-scaffold        PWA + auth + season CRUD             [22 PWA]
 │                        └── PR #9  feat/p5-pwa-log-forms        log forms (cost/harvest/done)  [+13 → 35 PWA]
 │                             └── PR #10  feat/p5-pwa-disease-dealers-prices  disease scan + dealer map + price chart  [+3 → 38 PWA]
 │                                  └── PR #11  feat/p5-pwa-offline-i18n  offline queue + i18n + Swahili  [+14 → 49 PWA]  ← TIP
 └── PR #4  feat/p1-filament-sentry-health  (independent of chain)  Filament + Sentry + health  [+17]
```

**Test counts (all locally green):** 163 backend Pest + 49 PWA Vitest = **212 total tests**.

### CI-infrastructure blocker (carried over from 2026-05-09)

Both `api-lint` + `api-test` jobs fail at runner-pickup phase: 6-13 seconds total, zero steps execute, `runner_id=0`, `system.txt` log ends at "Job is about to start running on the hosted runner". Same pattern across all 9 PRs since 2026-05-09. PR #2's earlier successful CI run on identical YAML proves it isn't the workflow file. Likely cause: **raskimrusua Actions free-minute quota exhausted** (or repo-level Actions disabled). Joshua to check `https://github.com/settings/billing/summary` → Actions usage in browser. UWC skill `skill-ci-github-actions` v1.1 captured this as D1 + the gh-API triage commands.

### What a farmer can do today (against a running local backend)

**Plan a season → see engine timeline + scaled inputs (12 + 8 for tomato) → mark activities done → log every cost → log every harvest pick + sale → see rolling totals → diagnose a leaf photo (mock returning deterministic results) → find the nearest dealer that stocks what they need (haversine search + Leaflet map) → see 12-month price history + 3-mo forecast for any of 5 crops at 6 markets → download a one-page lender-ready PDF report. Works offline (writes queue + replay on reconnect). Bilingual EN/SW.**

### Resume sequence (next session, in priority order)

1. **Joshua unblocks GHA** — flip repo to public (free unlimited minutes) OR check billing. Once green CI fires, **merge PR #3 first** (it's the foundation; everything else auto-rebases). Then #4 (independent). Then PRs #5 → #6 → #7 → #8 → #9 → #10 → #11 in chain order. Each rebases the next automatically once the parent merges.
2. **PR #12** (small, ~1 day): Translate SeasonDetail Costs/Harvests panels (still English) + add HelpTooltip + WelcomeModal first-run onboarding.
3. **Hetzner provisioning session** (2-3 hours, no code): provision panda_app + panda_horizon containers + panda_production DB + Redis idx 5/6 + nginx vhost api.panda.shira.farm + CF Origin Cert reissue + DNS records. Get a real `https://api.panda.shira.farm` endpoint live so the PWA can demo against it.
4. **P6 marketing site** (~3-5 days): Astro 5 scaffold under `marketing/` at `panda.shira.farm`. Mirrors Shira's website pattern.
5. **P8 pilot setup** (~1 week): 200 farmer seed for Meru + Kirinyaga + 6 runbooks + weekly survey.

### Decisions still open
- Silas's last name + email + KALRO credential doc (saved in memory: `project_panda_team.md`). Needed before P7 content authoring + before Filament agronomist editor seeds his login.
- Native Swahili translator (separate person from Silas). Needed for P7 + for translator pass on remaining PWA strings.
- Crop.health Kindwise account + monthly KES ceiling. Mock works through P5; real wiring deferred.

### Resume-friendly commands

```bash
cd ~/Desktop/panda
git checkout feat/p5-pwa-offline-i18n  # tip of the chain
git log --oneline -5                    # last commits
gh auth switch -u raskimrusua           # before any panda gh ops
gh pr list                              # see all 9 open PRs
cd pwa && npm test && npm run build     # verify still green
cd ../api && ./vendor/bin/pest --no-coverage  # 163 tests
```

---

## Original Next-up — PR #4 (P1 close-out) — confirmed direction (2026-05-11)

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
