# Panda — Project Documentation (Internal)

**Confidential. Do not share outside Upstate Web Co. without founder approval.**

> Structure mirrors MyChama's 20-section `PROJECT_DOCUMENTATION_INTERNAL.md` (proven across 3 production apps per BUSINESS.md §5).

---

## 1. Project Overview

- **Product:** Panda — PWA for Kenyan smallholder horticulture farmers
- **Origin:** Adapted from JAICA FarmTrack spec (1,397 lines, 14 sections). Marketing: "Inspired by JICA SHEP PLUS"
- **Status:** P5–P6 Pre-Launch (21 PRs merged, backend on Hetzner, PWA on CF Pages preview)
- **Repo:** github.com/raskimrusua/panda (public)
- **Domain:** panda.shira.farm (not yet wired)
- **Team:** Josh Mukui (founder), Silas (agronomist, named 2026-05-11), Claude (pipeline)

---

## 2. Architecture Overview

```
┌─────────────────────────────────────────────────┐
│                  Cloudflare                      │
│  ┌──────────────┐  ┌──────────┐  ┌───────────┐ │
│  │ Marketing     │  │ PWA      │  │ DNS       │ │
│  │ (Astro 5)    │  │ (React)  │  │           │ │
│  │ CF Pages     │  │ CF Pages │  │           │ │
│  └──────────────┘  └──────────┘  └───────────┘ │
└─────────────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────┐
│              Hetzner CX22 (shared)               │
│  ┌──────────────────────────────────────────┐   │
│  │  panda_app (PHP-FPM)                     │   │
│  │  panda_horizon (queue worker)            │   │
│  │  panda_nginx (reverse proxy)             │   │
│  │  Postgres 16 (shared with Shira)         │   │
│  │  Redis 7 (shared with Shira)             │   │
│  └──────────────────────────────────────────┘   │
└─────────────────────────────────────────────────┘
```

**Stack:** Laravel 11 + PHP 8.3 + PostgreSQL 16 + Redis 7 + Horizon + Filament + Sanctum
**Multi-tenancy:** spatie/laravel-multitenancy (row-level isolation via Tenant FK)
**Auth:** Sanctum (SPA tokens) + Google OAuth (planned)
**Queue:** Horizon — `default`, `analytics`, `alerts`, `notifications`

---

## 3. Database Schema

**Key models (tenant-scoped):**
- `tenants` — farm/cooperative (spatie package)
- `crops` — 17 crops (content library, JSON-backed)
- `seasons` — crop cycle with start/end dates
- `activities` — planting, weeding, fertilizing, harvesting
- `cost_records` — inputs (seeds, fertilizer, labor, water)
- `harvest_records` — yield per crop/season
- `disease_detections` — AI-powered (mock in P1-P4)
- `dealers` — input suppliers with GPS
- `market_prices` — daily prices from 30+ dealers
- `user_consents` — DPA 2019 append-only consent log

**Non-tenant models:**
- `users` — auth (Sanctum)
- `content_loaders` — JSON content cache management

---

## 4. API Design

**Base URL:** `https://api.panda.farm/api/v1/` (planned)
**Auth:** Bearer token (Sanctum)
**Format:** JSON:API-ish (data + meta envelopes)

**Route groups:**
```
auth:sanctum + tenant + consent
├── /seasons         CRUD + profit summary + charts
├── /crops           read-only (content library)
├── /activities      per-season log
├── /costs           per-season cost tracking
├── /harvests        per-season yield
├── /diseases        detection + history
├── /dealers         directory with GPS
├── /prices          market price history
└── /policies        DPA consent management
```

**Resources:** Always List + Detail pairs. NEVER return raw model arrays.

---

## 5. Multi-Tenancy Implementation

- **Package:** spatie/laravel-multitenancy v4
- **Isolation:** Row-level via `tenant_id` FK on every business model
- **Middleware:** `tenant` middleware resolves tenant from request context
- **Cross-tenant:** Returns 404 (never 403 — tenant leak prevention)
- **Feature flags:** `TenantSettings` model — per-tenant configurable via Filament

---

## 6. Security

- **Auth:** Sanctum SPA tokens, CSRF protection
- **CORS:** Pinned to PWA + marketing origins
- **Rate limiting:** Laravel throttle middleware (per-route)
- **Filament admin:** Role-gated + Cloudflare Access IP allowlist (planned for prod)
- **DPA 2019:** ConsentGate middleware, UserConsent append-only model
- **Disease AI cost ceiling:** `CROP_HEALTH_MAX_MONTHLY_KES` env var with mock fallback

---

## 7. DPA 2019 Compliance

**Consent gate (Rule L5, shipped 2026-05-15):**
- `UserConsent` model — append-only, unique on (user_id, policy_type, version)
- `ConsentGate` middleware — 409 + `TERMS_VERSION_OUTDATED` on stale users
- `RegisterRequest` enforces `terms_accepted` + `privacy_accepted`
- Reconsent at `/accept-terms` (outside AppShell to avoid redirect loops)
- Advisory disclaimers in `DiseaseDetectionResource` + `MarketPriceResource` payloads

**Pending:** ODPC registration, lawyer-reviewed policies, cyber insurance quote

---

## 8. Testing

- **Framework:** Pest 3.x
- **Database:** RefreshDatabase per test
- **Fakes:** Bus::fake(), Storage::fake(), Http::fake()
- **Coverage gate:** 70% minimum per PR
- **CI:** GitHub Actions — Pest + Pint + PHPStan

---

## 9. Deployment

- **Host:** Hetzner CX22 (shared with Shira)
- **Containers:** panda_app, panda_horizon, panda_nginx
- **CI/CD:** GitHub Actions → docker compose pull + up
- **PWA:** Cloudflare Pages (panda-pwa.pages.dev)
- **Marketing:** Cloudflare Pages (panda-marketing.pages.dev)
- **Domain:** panda.shira.farm (DNS not yet wired — weekend work)

---

## 10. Monitoring

- **Sentry:** Error tracking (wired in P1)
- **Horizon:** Queue monitoring dashboard
- **Health endpoint:** `/up` (Laravel default)
- **P8 additions planned:** UptimeRobot, backup verification, disk alerts

---

## 11–20. Sections to be populated as project matures

These sections mirror MyChama's internal doc structure. They will be filled in during P7 (content authoring) and P8 (pilot readiness):

11. Content Management (crops, diseases, translations)
12. Analytics Layer (crop profitability, price trends, disease prevalence)
13. Billing & Subscriptions (pricing tiers TBD)
14. Messaging & Notifications (SMS, push, email)
15. Offline & Sync (PWA offline queue)
16. Performance & Scaling (load testing TBD)
17. Third-Party Integrations (Crop.health Kindwise, M-Pesa, weather APIs)
18. Runbooks (incident response, backup recovery, SSL renewal)
19. Compliance & Legal (ODPC registration, ToS, privacy policy lawyer review)
20. Roadmap & Future (Phase 2 custom disease models, cooperative analytics)

---

*Document created: 2026-05-16 — P5/P6 scaffold. Fill remaining sections during P7–P8.*
