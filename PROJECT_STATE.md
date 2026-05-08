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
| P1 | Laravel backend skeleton + content + monitoring + agronomist editor | ⏳ Ready | Pipeline | 2 weeks from kickoff |
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
| Agronomist named (paid freelance, KALRO-credentialed) | Not named | P7 content authoring start |
| Native Swahili translator | Not named | P7 content authoring start (also P5 for UI strings) |
| Pricing tier model (Free / Plus / Pro at KES X / Y) | Not decided | P5 upgrade-modal design |
| Crop.health Kindwise account + monthly cost ceiling | Not signed up | P5 (mocked through P4) |
| Image data retention period | Working assumption: 24 months | P4 disease detection persistence |
| Disease photos for training data — opt-in/opt-out | Working assumption: opt-in default | P4 consent UI |
| Image consent UI text + DPA 2019 wording | TBD | P5 (review with lawyer alongside Shira's ToS work) |
| Domain — `panda.farm` vs alternatives | Working assumption: `panda.farm` | P6 marketing launch |
| GitHub repo — public or private | Working assumption: private | P1 first push |
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

---

## KNOWN ISSUES / RISKS

- **No engineering blocker for P1 kickoff** — confirmed by governance v1.2.
- **Content authoring is the long pole** — 17 crops × ~6 hours agronomist time + ~3 hours translation each = ~150 person-hours. Realistic: 6–10 weeks part-time. Joshua to source contractor before P7 starts.
- **Disease AI cost unknown until pilot** — at 200 farmers × estimated 5 scans/month × KES 3 ≈ KES 3,000/month. Acceptable. Cost guard envoirned in `CROP_HEALTH_MAX_MONTHLY_KES`.
- **Distribution risk** — standalone Panda has no built-in farmer pipeline (would have inherited Shira's farmers as a Shira module). Need explicit go-to-market in P8: county extension officers, KALRO partnerships, paid acquisition.
