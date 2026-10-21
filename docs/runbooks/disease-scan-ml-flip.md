# Disease-Scan ML Flip Runbook — Panda (mock → Kindwise Crop.health)

Status: **BLOCKED on Josh** — needs (1) a Crop.health (Kindwise) account + API key, (2) the monthly KES ceiling number. Do not flip until both exist.

## Current state (verified 2026-10-20, branch feature/legal-compliance-dpa-2019 @ c59c766)

- Provider binding is env-driven: `AppServiceProvider::register()` binds `CropHealthClient` via
  `config('services.crop_health.provider')` → `kindwise` ⇒ `KindwiseCropHealthClient`, anything else ⇒ `MockCropHealthClient` (deterministic, zero-cost, offline).
- The switch is `CROP_HEALTH_PROVIDER=mock` → `kindwise` in `/srv/panda/.env` (Hetzner). No code deploy needed for the flip itself.
- Real adapter (`KindwiseCropHealthClient`, PR #31) calls `POST {KINDWISE_API_URL}/identification` with `Api-Key` header, `images:[base64]`, `similar_images:false`, `classification_level:"species"`, optional `crop` hint.
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
     -d '{"images":["<base64 of a small leaf photo>"],"similar_images":false,"classification_level":"species"}'
   ```
   Expect HTTP 200 with `result.classification.suggestions`.

## Flip procedure (Hetzner)

1. Edit `/srv/panda/.env`:
   ```
   CROP_HEALTH_PROVIDER=kindwise
   KINDWISE_API_KEY=<key>
   # KINDWISE_API_URL + KINDWISE_TIMEOUT left at defaults
   # CROP_HEALTH_MAX_MONTHLY_KES set to the agreed ceiling (documentation only)
   ```
2. Rebuild config + restart:
   ```
   cd /srv/panda && docker compose exec panda_app php artisan config:clear && docker compose restart panda_app
   ```
   (If the deploy bakes `php artisan config:cache` into the image, rerun the bake or `config:clear` at boot instead — check `/srv/panda/docker-compose.prod.yml` entrypoint.)
3. Verify: `GET https://panda-api.shira.farm/api/v1/health` → 200, and the HealthCheck's crop_health probe should flip from `skipped` to its live status (`app/Services/Health/HealthCheck.php`).
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
