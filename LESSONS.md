# LESSONS.md — Panda

**Build lessons and debugging knowledge from the first Laravel project through the UWC pipeline.**

> Panda is 11 days old (P0 governance report: 2026-05-07). This file will grow with every milestone.
> Lessons are captured in real-time via the pipeline's upstream flow (build notes → retro → skills bump).

---

## Current Lessons (P1–P5, as of 2026-05-16)

| # | Lesson | Source |
|---|--------|--------|
| 1 | GitHub Actions public-repo billing block — repo must be public for GHA minutes on free tier | P1 CI setup |
| 2 | Horizon requires `pcntl` extension in Dockerfile — missing causes silent worker failures | P1 Docker |
| 3 | PWA PDF downloads need token-bearing URL pattern — direct API access returns JSON 401 | P5 PWA |
| 4 | Pest assertions need `->orderBy('id')` to prevent flaky CI ordering on parallel runs | P3 Tests |
| 5 | Non-tomato crops have 0 activities — Season Engine only generates from JSON content files, which only tomato has | P7 simulation |
| 6 | Seed cost/harvest data is tomato-specific — staking costs applied to onions, wrong agronomically | P7 simulation |
| 7 | Prod Dockerfile bakes code at build time — adding a new Artisan command requires `docker compose build app` | P7 deploy |


These 4 lessons have been backported to `uwc-ops#5` (awaiting Joshua review).

---

## Architecture Decisions (why Laravel for Panda and not Django)

| Decision | Rationale |
|---|---|
| Laravel (not Django) | JAICA spec was already Node+Postgres; Laravel gave PHP ecosystem + Filament admin for free |
| spatie/laravel-multitenancy | Mature package, same row-level isolation pattern proven in Shira + MyChama |
| brick/money | Mandatory for all KES fields — same discipline as Django DecimalField |
| Filament admin | Agronomist content editor — role-gated, Cloudflare Access IP allowlist |
| React PWA (same as Shira) | Shared frontend expertise across products |
| Separate Hetzner stack | Shared CX22 box but separate Docker compose — isolation without new VPS cost |

---

## Bug Registry (will populate as project matures)

| ID | Description | Status | Fix |
|---|---|---|---|
| PANDA-001 | ActivityLog flaky test assertions — missing `orderBy('id')` | ✅ Fixed (PR #17) | Added `->orderBy('id')` to SeasonModelTest + TenantTest |
| PANDA-002 | PDF download returned JSON 401 from PWA | ✅ Fixed (PR #17) | Token-bearing URL + auth middleware exemption |
| PANDA-003 | Horizon container crash — missing pcntl | ✅ Fixed (PR #16) | Added `pcntl` to Dockerfile |
| PANDA-004 | Non-tomato crops (kale, onion) have 0 Season Engine activities — no JSON content files exist | 🔴 Open | Create kale.json + onion.json content files in resources/content/crops/ |
| PANDA-005 | Cost/harvest simulation seed data hardcoded for tomato across all crops | 🟡 Open | Parameterize seedCostsForSeason/seedHarvestsForSeason by crop type |
| PANDA-006 | Docker image must be rebuilt for code changes — files not bind-mounted in prod | ✅ Fixed (workaround) | Rebuild with `docker compose -f docker-compose.prod.yml build app` after file changes |


---

## Skills Exercised

14 Laravel skill files exercised through P1–P5 — see `~/Desktop/uwc-web-co/02-apps/panda/skills-contributed.md`.

---

*Last updated: 2026-05-16 — P5/P6 state. This file will be updated after each milestone retro.*
