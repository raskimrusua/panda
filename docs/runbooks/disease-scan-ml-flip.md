# Disease-Scan ML Flip Runbook — Panda (mock → Kindwise Crop.health)

Status: **RESOLVED + FLIPPED 2026-10-27** — Kindwise key provisioned, adapter payload fixed (PR #40), env flipped on Hetzner, live verification passed. See "Resolution record" at the bottom.

## Current state (verified 2026-10-20, branch feature/legal-compliance-dpa-2019 @ c59c766)

- Provider binding is env-driven: `AppServiceProvider::register()` binds `CropHealthClient` via
  `config('services.crop_health.provider')` → `kindwise` ⇒ `KindwiseCropHealthClient`, anything else ⇒ `MockCropHealthClient` (deterministic, zero-cost, offline).
- The switch is `CROP_HEALTH_PROVIDER=mock` → `kindwise` in `/srv/panda/.env` (Hetzner). No code deploy needed for the flip itself.
- Real adapter (`KindwiseCropHealthClient`, PR #31) calls `POST {KINDWISE_API_URL}/identification` with `Api-Key` header and a **minimal body** — `images:[base64]` only. PR #40 (2026-10-27) removed `similar_images:false`, `classification_level`, and the `crop` hint: live verification against model crop_health:1.2.1 showed every modifier except `similar_images=true` is rejected with HTTP 400. Treatments are sourced from the local PCPB `TreatmentCatalogue` (PR #41) because this API version returns no treatment details.
- The PWA needs **no change** — `provider` comes back in the API payload; the scan UI already shows "demo mode" when `provider === 'mock'` and hides it otherwise. PWA build (`VITE_API_URL`) untouched.

## Env vars (all in /srv/panda/.env — see comments in api/.env.example lines 60-63, 79-82)

| Var | Default | Meaning |
|---|---|---|
| `CROP_HEALTH_PROVIDER` | `mock` | THE switch. `kindwise` = real API, anything else = mock |
| `KINDWISE_API_KEY` | (empty) | Crop.health account API key. Client throws if empty while provider=kindwise |
| `KINDWISE_API_URL` | `https://crop.kindwise.com/api/v1` | Base URL (leave default) |
| `KINDWISE_TIMEOUT` | `20` | HTTP timeout seconds |
| `CROP_HEALTH_API_KEY` | (empty) | **Legacy/unused by the adapter** — the client reads `services.kindwise.key` (KINDWISE_API_KEY). Leave empty or set for parity; do not rely on it |
| `CROP_HEALTH_MAX_MONTHLY_KES` | `20000` | **Declarative only.** No code reads this value — there is no in-app usage cap. Real enforcement = the Kindwise account's own quota/limit |

## Crop.health (Kindwise) account steps

1. Create the account at https://crop.health (Kindwise) and choose the **Plant.id identification** plan suitable for pilot volume (start small).
2. Copy the API key from the dashboard.
3. Confirm the monthly quota on the account — set Kindwise-side spending/volume limits to match the pilot ceiling Josh picks (anchor: KES 20,000/mo default).
4. Optional smoke test from the server BEFORE flipping:
   ```
   curl -s -X POST https://crop.kindwise.com/api/v1/identification \
     -H "Api-Key: <KEY>" \
     -H "Content-Type: application/json" \
     -d '{"images":["<base64 of a small leaf photo>"]}'
   ```
   Expect HTTP 200 with `result.disease.suggestions` (and `result.is_plant`).

## Flip procedure (Hetzner)

1. Edit `/srv/panda/.env`:
   ```
   CROP_HEALTH_PROVIDER=kindwise
   KINDWISE_API_KEY=<key>
   # KINDWISE_API_URL + KINDWISE_TIMEOUT left at defaults
   # CROP_HEALTH_MAX_MONTHLY_KES set to the agreed ceiling (documentation only)
   ```
2. Rebuild + restart (env-only flips can use `config:clear` + `restart`; this flip also shipped adapter code changes, so a full image rebuild was required — 2026-10-27):
   ```
   cd /srv/panda && docker compose -f docker-compose.prod.yml build app && docker compose -f docker-compose.prod.yml up -d app
   ```
   (If the deploy bakes `php artisan config:cache` into the image, rerun the bake or `config:clear` at boot instead — check `/srv/panda/docker-compose.prod.yml` entrypoint.)
3. Verify: `GET https://panda-api.shira.farm/api/v1/health` → 200. NOTE: the HealthCheck's `crop_health` probe is currently hardcoded `skipped` (it is a placeholder, not a live probe) — do not use it as a flip signal. Verify with a real call instead (see Resolution record).
4. Live test: submit one real photo in the PWA disease scan. Check the new `disease_detections` row has `provider = 'crop_health'` + `top_diagnosis` + `treatments` populated.
5. Audit: the `provider` column (`mock` vs `crop_health`) is the billing audit trail — count `crop_health` rows weekly against the Kindwise dashboard.

## Failure behaviour + rollback

- If the Kindwise call fails (bad key, quota, timeout), `KindwiseCropHealthClient::diagnose()` **throws** — there is NO automatic mock fallback in the controller. The request 500s and the transaction rolls back (no row, no charge). Farmers see an error; that's acceptable for pilot but means a broken key = broken scan feature.
- Rollback is env-only, no redeploy:
  ```
  cd /srv/panda && sed -i 's/^CROP_HEALTH_PROVIDER=kindwise/CROP_HEALTH_PROVIDER=mock/' .env \
    && docker compose exec panda_app php artisan config:clear && docker compose restart panda_app
  ```
- Partial-failure consideration: if Kindwise throttles intermittently, consider later adding a mock-fallback-on-4xx path in the controller (deliberately NOT built now — a silent mock answer on a paid feature would mislead farmers).

## Open follow-ups (post-pilot)

- Wire a real usage guard: monthly KES tracker keyed to `CROP_HEALTH_MAX_MONTHLY_KES` (count `provider='crop_health'` scans × per-call cost) with a hard-stop when the ceiling hits. Rule L1 / anti-pattern "Disease AI cost not env-capped" — currently the cap is only Kindwise-side.
- Daily R2 export job for opted-in training photos (already on the backlog, PR #35 notes).

## Resolution record (2026-10-27)

- Key provisioned; ceiling anchor KES 20,000/mo (Kindwise-side limit; the in-app cap is still declarative — the usage-guard follow-up above stands).
- **Adapter fix (PR #40)**: live calls 400'd on `similar_images:false` / `classification_level` / `crop`. Minimal payload shipped; only `similar_images=true` is an accepted modifier.
- **Treatments gap (PR #41)**: the API version returns no treatment details (`details` modifier rejected), so treatments come from the local PCPB `TreatmentCatalogue` (mock + live share it). P0 season binding + P1 plan-treatment→CostEntry shipped in the same PR.
- **Server**: `/srv/panda/.env` gained `KINDWISE_API_KEY` + `CROP_HEALTH_PROVIDER=kindwise`; `panda_app` image rebuilt + restarted; binding verified (`KindwiseCropHealthClient`); a real identification call from inside the container returned HTTP 200 with `result.disease.suggestions`.
- **PWA**: deployed to production (`app.panda.shira.farm`) with the season picker + treatment planning UI; new bundle verified live by asset hash.
- **Deploy gotchas**: the TUI sandbox's `node` is VS Code's Electron shim — run wrangler via `/usr/local/bin/node ./node_modules/wrangler/bin/wrangler.js`; pin `wrangler@3.x` (latest major dropped `pages deploy`); deploys from feature branches land on preview aliases — use `--branch=main` for production.
