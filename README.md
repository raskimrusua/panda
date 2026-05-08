# Panda

A horticulture-first farming OS for Kenyan smallholders. Adapted from the JAICA FarmTrack specification, inspired by JICA SHEP PLUS methodology ("grow to sell" — market-led farming decisions).

**Standalone product** — sister to Shira (livestock farm management) but architecturally separate: own auth, own users, own data, own deploy. Not a Shira module.

## What it does

A farmer enters a crop, acreage, planting date, and irrigation type. Panda generates the full season blueprint: activity timeline (nursery → transplant → growth → harvest), acreage-scaled input list (seed, fertilizer, chemicals, labour), cost budget, market price forecast, off-season opportunity prompts, AI disease detection from a phone photo, and the nearest agro-dealer stocking the recommended inputs.

## Status

**P1 ready to start.** Pre-build governance complete (combined score 7.05/10, clears the 6.5 gate). All 14 Laravel skill files exist in `~/Desktop/uwc-web-co/00-skills/app-build/laravel/`. UWC pipeline is the build vehicle from here on.

Track current state in `PROJECT_STATE.md`.

## Stack

- **Backend:** Laravel 11 + PHP 8.3 (UWC's first Laravel project)
- **Database:** Postgres 16 (own DB, separate from Shira)
- **Queue:** Laravel Horizon on Redis
- **Admin:** Filament 3
- **Frontend:** React PWA (own app, own URL — not shared with Shira's PWA)
- **Marketing:** Astro on Cloudflare Pages
- **Storage:** Cloudflare R2 default, AWS S3 toggle per skill-laravel-storage-toggle
- **Auth:** Standalone JWT (Sanctum) — Panda issues its own tokens, no shared identity with Shira
- **Hosting:** Hetzner CX22 (shared box with Shira initially; separate when scale demands)
- **Disease AI:** Crop.health API (Kindwise) — mocked in P1–P4, live in P5

## Why standalone (not a Shira module)

Originally planned as a Shira module under `shira.farm/crops`. Decision 2026-05-07 to separate:
- Different stage of progress (Shira is live; Panda is at P1)
- Different positioning (livestock vs horticulture; different pilot counties; different value prop)
- Avoids the integration risk surfaced in governance (cross-backend sync, shared JWT rotation, mirror-row drift)
- Cleaner story for any future investor / acquirer
- Trade-off accepted: loses the "Shira farmer also gets crops for free" distribution channel

## The 17 crops (full Phase 1 scope)

Tomato, Kale (Sukuma Wiki), Cabbage, Bulb Onion, French Beans, Capsicum, Chili, Eggplant, Potato, Watermelon, Amaranthus, Black Nightshade, Cowpea Leaves, Avocado, Banana, Mango, Passion Fruit. Bilingual (English + Swahili). Marketing framing: **"Inspired by SHEP PLUS"** (no formal JICA partnership at launch).

## Documents

- `CLAUDE.md` — context for any Claude Code session working on Panda
- `PROJECT_STATE.md` — current status, what's done, what's next
- `docs/MASTER_PROMPT.md` — the JAICA-derived master context for Claude Code build sessions
- `docs/GOVERNANCE_REPORT_2026_05_07.md` — pre-build governance report (mirrored from farmcore/docs)
- Plan: `~/.claude/plans/piped-knitting-blum.md`

## Repos / domains (TBD)

- Repo: this directory (push to GitHub when P1 produces first commit)
- Domain: `panda.farm` placeholder — confirm during P1
- API origin: `api.panda.farm`
- App: `app.panda.farm`
- Marketing: `panda.farm`

## Pipeline

Built via UWC pipeline (~/Desktop/uwc-web-co/), not by direct coding. Phase transitions gated by:
- 14 Laravel skills (~/Desktop/uwc-web-co/00-skills/app-build/laravel/)
- 6 governance agents (10-agent system, 6 relevant to product builds)
- Skill invocation discipline: `/new-app panda` → milestone scaffolding follows

Joshua's role: decide the deferred items as they surface (agronomist, SW translator, pricing tier, image policies). Pipeline runs the engineering.
