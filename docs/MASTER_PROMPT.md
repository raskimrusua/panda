# Panda — Master Context Prompt for Claude Code Sessions

**Paste this entire block at the start of every new Claude Code session building Panda.** It establishes the full project context. Adapted from the JAICA FarmTrack spec §2 to fit Panda's standalone Laravel architecture.

---

You are helping build **Panda** — a multi-tenant SaaS farming operating system for Kenyan smallholder horticulture farmers, inspired by JICA SHEP PLUS agronomic methodology.

## PROJECT MISSION
Help Kenyan smallholder farmers make more money from their crops by giving them a precise, personalised season plan from seed to sale — with costs, tasks, AI disease detection, input sourcing, and market price intelligence built in.

## TECH STACK (non-negotiable unless you flag a specific reason)

```
Backend:    Laravel 11 + PHP 8.3 + Postgres 16 + Redis 7
Queue:      Laravel Horizon (Redis-backed)
Admin:      Filament 3
Auth:       Laravel Sanctum (own JWT — Panda is standalone, NOT integrated with Shira)
Money:      brick/money + bcmath (PHP has no native Decimal)
Multi-tenant: spatie/laravel-multitenancy (single-DB row-based)
Tests:      Pest 3
Static:     PHPStan level 6 (climbing)
Style:      Laravel Pint
Mobile:     Progressive Web App (React 19 + Vite 7 + TypeScript strict)
Marketing:  Astro 5 on Cloudflare Pages
Storage:    Cloudflare R2 (default) — toggleable to AWS S3 via STORAGE_BACKEND env
Disease AI: Crop.health API (Kindwise) — mocked in P1–P4, live in P5
Payments:   M-Pesa Daraja STK Push (KES subscriptions)
SMS:        Africa's Talking (transactional + reminders)
Email:      Resend (transactional + digests)
Hosting:    Hetzner CX22 (shared box with Shira initially; separate when scale demands)
```

## CORE PRINCIPLES

1. **Offline-first** — core PWA features work with zero internet, sync when online.
2. **Swahili + English** — every user-facing string passes through `t('key')` (i18next). No hardcoded English ships.
3. **Low-end Android** — design for 2 GB RAM, 32 GB storage, 3G connection.
4. **Acreage-scaled** — every quantity, cost, and timeline scales to the farmer's acreage.
5. **Inspired by SHEP PLUS** — agronomic content references JICA SHEP PLUS publications. **Marketing claim is "inspired by," not "endorsed by" or "in partnership with"** — no formal JICA partnership at launch.
6. **PCPB/KEPHIS only** — only Kenya-registered products appear in recommendations.
7. **Standalone product** — Panda is its own product. NOT a Shira module. Own auth, own users, own data, own URL.

## PHASE 1 SCOPE (build only this)

- **17 crops**: Tomato, Kale, Cabbage, Bulb Onion, French Beans, Capsicum, Chili, Eggplant, Potato, Watermelon, Amaranthus, Black Nightshade, Cowpea Leaves, Avocado, Banana, Mango, Passion Fruit
- **Season Engine** — given (crop, acreage, planting_date, irrigation_type, county), generates the full activity timeline + acreage-scaled input list + cost budget
- **Cost accumulator** — 9 input cost categories (seed, land prep, fertiliser, chemical, labour, irrigation, packaging, transport, other)
- **AI disease detection** — Crop.health API (mocked initially, live in P5 with monthly cost cap)
- **Input list generator + dealer directory** — GPS-sorted agro-dealer map (no transactions yet)
- **Education layer** — disease library + contextual tips
- **M-Pesa subscription billing** — KES tier TBD
- **PWA** — Android-first, offline-first
- **Marketing site** — Astro on Cloudflare Pages

## DO NOT BUILD IN PHASE 1 UNLESS EXPLICITLY ASKED

- iOS app, USSD, group buying, input credit, forward contracts
- Custom TFLite model (Phase 2)
- B2B tenant portal (Phase 2)
- Uganda / Tanzania expansion
- Direct integration with Shira (Panda is standalone)

## REPOSITORY STRUCTURE

```
~/Desktop/panda/
├── api/             ← Laravel 11 backend (created by P1)
├── pwa/             ← React PWA (created by P5)
├── marketing/       ← Astro marketing site (created by P6)
├── docs/            ← project docs
├── README.md
├── CLAUDE.md        ← project rules — READ EVERY SESSION
└── PROJECT_STATE.md ← current state — UPDATE EVERY DELIVERABLE
```

## SKILL FILES TO REFERENCE

When building a new model, endpoint, observer, etc., read the matching skill file from:
```
~/Desktop/uwc-web-co/00-skills/app-build/laravel/
```
14 skill files exist. Index at `README.md` in that directory. Always read the skill file BEFORE writing code.

## WHEN I ASK YOU TO BUILD A FEATURE

Always:
1. Read the relevant Laravel skill file(s) first
2. Read `~/Desktop/panda/CLAUDE.md` for the architecture rules
3. Read `~/Desktop/panda/PROJECT_STATE.md` for current state
4. Write Pest tests alongside the code (not after)
5. Pair migrations with their model in the same commit
6. Use FormRequest for validation, Resource for response, never `toArray()`
7. Use brick/money for money, never float
8. Wrap money mutations in `DB::transaction()`
9. Make queued jobs idempotent + ShouldQueue + tries-bounded
10. Return 404 (not 403) for cross-tenant misses
11. Update `PROJECT_STATE.md` when a deliverable is complete

## FORBIDDEN

- Hardcoding `Storage::disk('r2')` — use the default disk (per skill-laravel-storage-toggle)
- `(float) $cost` arithmetic on Money attributes (per skill-laravel-money-decimal)
- `$model->toArray()` returned from controllers (per skill-laravel-resource)
- `$request->validate(...)` in controllers (per skill-laravel-form-request)
- 403 instead of 404 for cross-tenant misses (per skill-laravel-multitenancy)
- Hardcoded English strings in PWA (per CLAUDE.md L3)
- Disease photo uploads without consent flag (per CLAUDE.md L4)
- Auto-pushing to git from inside controllers / Filament actions in production
- Skipping tests "for speed"

## IMPORTANT

Every subsequent prompt in this session assumes this master context has been loaded. Re-read `CLAUDE.md` and `PROJECT_STATE.md` at the start of every NEW session.

The original JAICA build doc (`~/Downloads/JAICA_FarmTrack_Build_Documentation.docx`) has 12 copy-paste prompts for the Node.js / Express / Prisma / React Native stack. **Those prompts are NOT directly usable** — they assume a different stack. Instead, the equivalent guidance is encoded in the 14 Laravel skill files. Use the skills, not the JAICA prompts.

---

*This master prompt is versioned and immutable. If the stack or principles change, bump the version number and add a changelog entry. Current version: 1.0, dated 2026-05-07.*
