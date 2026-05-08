# Shira Crops Module — Pre-Build Governance Feasibility Report

**Product:** Shira Crops (JAICA FarmTrack adapted as a Shira module on Laravel)
**Evaluated:** 2026-05-07
**Evaluated by:** UWC Agent Governance System (simulated — same harness as `GOVERNANCE_REPORT_2026_04_15.md`)
**Report version:** 1.0 (pre-build P0 gate)
**Spec source:** `~/Downloads/JAICA_FarmTrack_Build_Documentation.docx` (38 pages, 12 Claude Code prompts)
**Plan reference:** `~/.claude/plans/piped-knitting-blum.md`

This is a **pre-build** governance pass. Unlike the 2026-04-15 Shira report (which evaluated live product), this report evaluates the *plan* before any code is written. The gate decision: **score ≥ 6.5/10 → proceed to P0.5 + P1. Below 6.5 → remediate first.**

---

## 1. Ghost User Report

**Persona:** Mwende, 38, smallholder horticulture farmer in Buuri (Meru County). 1.2 acres — currently growing tomato (0.8 ac) and kale (0.4 ac). Owns a low-end Android phone (Tecno Spark, 2 GB RAM, 32 GB storage). Already uses the Shira PWA for her 4 dairy cows. Found Shira through her sub-county's KALRO extension officer. Reads English slowly, prefers Swahili. Does not have a debit card; pays everything via M-Pesa.

### Discovery (Score: 7/10)

| Signal | Assessment |
|---|---|
| Crops landing page (`shira.farm/crops`) — *planned, not built* | Will mirror existing `/features` cards. Reasonable bet given Astro skill maturity. |
| Cross-module discovery from existing Shira account | Plan adds a sidebar nav entry gated on `crops_enabled` — Mwende sees it the day Shira flips the flag. Strong UX continuity. |
| Bilingual messaging | EN + SW commitment in plan; existing site has SW-aware copy; no SW translation pipeline yet for crop content. **Gap.** |
| Trust signals | Spec rests on JICA SHEP PLUS branding. Without a signed JICA/KALRO partnership, this is an unverified claim. **Critical risk.** |
| Free tier visibility | Plan says crops feature gates on Shira `SubscriptionPlan` — pricing matrix not yet specified. **Defer.** |

**Friction:** A new visitor cannot tell if Shira *has* crops yet vs *plans* crops. Plan should defer the marketing-page launch (P6) until P5 is feature-complete to avoid disappointment.

### Onboarding (Score: 6/10)

| Signal | Assessment |
|---|---|
| Single login (Shira JWT shared) | Strong — Mwende doesn't make a second account. Plan's auth boundary is clean. |
| First crop creation | Plan: NewSeasonPage → crop picker grid → acreage → planting date → engine preview. Mirrors livestock onboarding shape. Reasonable. |
| County / GPS capture | Mwende's existing Farm record has county; engine reads it. Good reuse. |
| Language toggle | Plan mentions but does not specify *who* owns the SW translations. **Gap — P7 content authoring must include native-SW review.** |
| No agronomist on speed-dial | Plan offers offline symptom decision tree as fallback when Crop.health is unavailable. Good. But: tree only as good as content authoring (P7). |

**Critical finding (carry-over from the 2026-04-15 report):** `app.shira.farm` was in maintenance mode at last governance pass. Per CLAUDE.md, status is now "current state: ON" — meaning still in maintenance. **The Shira PWA itself must be live before crops can launch into a real PWA shell.** This is not a crops blocker per se, but the crops launch depends on it being resolved first.

### First Value (Score: 5/10 — estimated)

What Mwende needs in her first 5 minutes:
1. Create a tomato season for 0.8 acres → **Plan: ✓** (P3 endpoint, P5 form)
2. See a clear timeline of what to do this week → **Plan: ✓** (Season Engine output, SeasonTimelineCard)
3. See a budget she can afford → **Plan: ✓** (cost summary bar)
4. See where to buy seed locally → **Plan: ✓** (DealerMapPage, P4)
5. Photograph a wilting plant and get an answer → **Plan: ✓ (mock) / partial (real Crop.health gated)**

**Concern:** the Season Engine output for tomato relies on the Tomato JSON content file. If P7 content authoring slips, the engine produces garbage timelines. Plan acknowledges this and seeds Tomato in P1 as the canonical reference — **good**, but only 1 of 17 crops at P1 means 94% of Mwende's potential peers (kale, cabbage, etc.) hit "coming soon" at launch.

### Retention Signals (Score: 5/10)

| Signal | Status |
|---|---|
| Activity reminders (push/SMS) | Plan: route through Shira's NotificationService. Inherits Shira's existing-but-not-yet-active SMS gap. |
| Weekly digest | Not specified in plan. **Recommend adding to P5.** |
| Disease detection feedback loop | Plan: thumbs up/down posts to `/disease/:id/feedback`. Good — feeds Phase 2 custom model. |
| Harvest log → revenue projection | Plan: HarvestLog observer recomputes projection. Strong retention hook ("see your earnings update live"). |
| Off-season opportunity prompts | Plan: Season Engine + price forecast suggests "plant tomato in March to hit June peak". This is the SHEP "grow to sell" core — **unique retention asset**. |

### Ghost User Overall: 5.75/10

The plan is shaped right for Mwende. The big gaps are content (no SW translator named, only 1 of 17 crops at P1), trust (no JICA partnership signed), and the upstream Shira PWA still being in maintenance.

---

## 2. Ghost Creator Scorecard

**Persona:** The agronomist + content-ops team that has to author and maintain 17 crop JSON files + ~85 disease JSON files + dealer directory + market price ingestion.

### Content Workflow (Score: 4/10)

| Capability | Status |
|---|---|
| Filament admin editor for crops/diseases | **Planned, not built** (P1 scaffold + P7 polish) |
| JSON Schema validation | **Planned** (`opis/json-schema` in Laravel) |
| Bilingual content authoring | **Gap** — no defined SW review pipeline |
| Agronomist sign-off workflow | **Gap** — plan mentions it but does not specify who, how, or with what tooling |
| Version history | Filament + activity log handles this; **acceptable** |
| GitHub-based PR review (alternative to Filament) | Plan defers this question to P0 governance — **decide here** |

**Decision needed in this report:** **Filament editor + admin sign-off button.** Rationale: agronomists are not engineers. Forcing them to write PRs for a JSON file is a hidden cost that makes content authoring brittle. Filament can render a structured form per crop (timeline activities, input list, varieties, diseases) and a "submit for review" button that creates a `ContentReview` record. An admin (Joshua or appointed reviewer) approves; on approval, the JSON file is regenerated from DB and committed via a Laravel Artisan command + GitHub API. This is more work in P1 (~+2 days) but saves immense pain in P7.

### Publishing Speed (Score: 5/10)

- 17 crops × ~6 hours each (timeline + inputs + benchmark prices + 5 diseases) = ~100 agronomist hours = 2.5 weeks at full-time, more realistically 6–8 weeks part-time.
- Plus ~50 PCPB-registered products lookup file = ~1 week.
- Plus SW translation = +50% on every node, by a separate person.
- **Realistic content authoring: 6–10 weeks. Plan says 4–8 weeks. Adjust upward.**

### SEO Tools (Score: 7/10)

- Inherits existing SEO infrastructure (sitemap, JSON-LD, plausible, D1 SEO overrides).
- 17 crop pages + 85 disease pages = 102 new indexable URLs at launch — **strong organic-search position** for queries like "kale farming Kenya" or "tuta absoluta tomato treatment".
- Recommend: each crop page targets 3 long-tail queries explicitly in the plan (one for novices, one for experienced, one for the off-season window angle).

### Pricing Page Updates (Score: 6/10)

Plan defers the pricing tier strategy. Two options:
1. **Crops included free in existing tiers** (Mkulima/Shamba/Boma/Enterprise) — simplest, but cannibalises crops as a separately monetisable product.
2. **Crops = its own add-on** (e.g., +KES 200/mo for crops on top of any tier) — better unit economics, more billing complexity.

**Recommendation:** Option 2, but defer the actual decision to a separate pricing analysis. P0 governance only requires that the plan *acknowledge* the choice exists.

### Ghost Creator Overall: 5.5/10

Strong on infrastructure, weak on the content authoring labour pipeline. The Filament editor + sign-off workflow recommendation, if accepted, lifts this to ~7/10.

---

## 3. Stack Optimiser Recommendation

**The Laravel question** — is it the right tool, given UWC's Django-only history?

### Architecture Fitness (Score: 7/10)

| Dimension | Verdict |
|---|---|
| Domain fit | Laravel is fine for this domain. Eloquent + observers handle the season-engine triggering cleanly. Filament is a real win for content authoring UI. |
| Pattern reuse from Shira | Zero (different language). Patterns must be *mirrored*, not imported. Acceptable cost given it's a deliberate skill investment. |
| Per-customer storage toggle | **Laravel wins decisively** — `Storage` facade abstracts R2/S3 natively. Solves the "customer prefers AWS" requirement essentially for free. |
| Money handling | Eroded vs Django. PHP has no native `Decimal`. `brick/money` is correct but easy to forget. Linting + code review must enforce. |
| Multi-tenancy | Spatie/laravel-multitenancy is mature, proven, equivalent to Shira's farm-FK pattern. |
| JWT verification | `firebase/php-jwt` + custom Sanctum guard is ~30 LOC. Low risk. |
| Queue parity | Horizon ≥ Celery for ergonomics. Equivalent for correctness. |

**Concern:** Building a *first* Laravel project with a 14-week scope is ambitious. There will be Laravel-idiom learning costs in P1–P3 (estimate +20% timeline). Plan should pad each Laravel-side phase by 20%.

### Deployment Maturity (Score: 6/10)

| Dimension | Status |
|---|---|
| Hetzner Docker pattern | Proven for Django — extensible to PHP-FPM. New service in compose is well-trodden. |
| Database isolation | Plan: separate Postgres DB on same instance. **Acceptable** for cost; **less acceptable** for blast-radius isolation (a Postgres outage takes down both products). Open question deferred. |
| Cross-backend sync (farm cache) | Plan: nightly Celery → POST to Laravel. Acceptable. **Risk:** if Shira changes a farm name and the next 23 hours show stale data on crops side. Recommend: synchronous webhook on FarmSettings.save instead of nightly batch. |
| nginx vhost for `api-crops.shira.farm` | Standard pattern. Easy. |
| SSL via existing certbot | Add another domain to the cert. Trivial. |

### Cost Efficiency (Score: 8/10)

- No new vendor (same Hetzner CX22 — adds ~+200 MB RAM for PHP-FPM, fine)
- No new database server
- No Supabase, no AWS
- Crop.health API is variable; ceiling depends on pilot cap
- Filament 3 is OSS, no licence cost
- Estimated added monthly cost: **KES 0** (infra) + KES TBD (Crop.health API at pilot scale)

### Scalability (Score: 7/10)

- Plan supports 200 pilot farmers comfortably. 1,000+ farmers needs the standard Laravel scaling moves (Horizon worker pool, opcache tuning, Postgres connection pooling — none of which UWC has done before in Laravel).
- Image storage at 200 farmers × ~10 disease scans/season × 1 MB compressed ≈ 2 GB/season. R2 free tier easily covers.
- No identified architectural blocker to scale.

### Stack Optimiser Overall: 7.0/10

**Verdict:** Laravel is a defensible choice **provided** UWC commits to P0.5 (skill bootstrap) before P1. Without that, it's a recipe for slow P1.

---

## 4. Admin Agent (Workflow Inspector)

### Workflow Completeness (Score: 5/10)

| Workflow | Status |
|---|---|
| Farm flips `crops_enabled` flag | Plan: settings UI on Shira side. Migration risk: live data, must use `/migrate-safe`. |
| Farmer creates first crop season | Plan: complete (P5). |
| Disease scan offline-first | Plan: offline tree + queued upload. Solid. |
| Cost entry from receipt photo | Plan mentions `receipt_photo_url` in CostEntry model. Storage ✓. OCR? Not in scope. |
| Harvest log → revenue → tax-ready report | Plan has revenue. Tax-ready (KRA-format) report **not specified**. Defer to a later phase. |
| Pilot farmer onboarding via extension officer | Plan: P8 admin seed script. Doesn't address the human-side onboarding (training, materials, support contact). **Gap.** |

### Monitoring (Score: 4/10)

| Signal | Status |
|---|---|
| Laravel error tracking | Plan does not specify Sentry / GlitchTip. **Add to P1.** |
| Horizon dashboard | Built-in, but who watches it? **Add ops runbook in P1.** |
| Crop.health API failure rate | Plan does not specify alerting. **Add: alert if failure rate > 5% in 1h.** |
| Cross-backend sync health | Plan does not specify alerting. **Add: alert if Shira→Crops farm sync fails 3 consecutive runs.** |

### Operational Readiness (Score: 5/10)

| Signal | Status |
|---|---|
| Backup strategy for crops Postgres | Plan: same backup script as Shira. **Verify the script picks up the new DB.** |
| Disaster recovery (rebuild from backup) | Plan inherits Shira's pattern. Untested for crops shape. |
| Health check endpoint | Plan: `/api/v1/health/`. Good. |
| Pilot exit criteria | Plan has failure threshold (<30% logging >3 activities in 60 days). Strong. |
| Pilot success criteria for go/no-go to full launch | Plan mentions but does not define: NPS > 40, daily active > 50%, willingness to pay > 30%. **Quote them in plan.** |

### Admin Agent Overall: 4.7/10

The biggest gap: monitoring and operational runbooks are *implicit*. The plan needs an explicit P1 deliverable for Sentry + Horizon monitoring + Crop.health alerting + sync alerting.

---

## 5. Investor Agent (Business Viability)

### Market Fit (Score: 7/10)

- Kenya horticulture is a real market: 4M+ smallholder farmers, ~30% growing horticulture commercially
- JAICA SHEP PLUS already has farmer relationships through county extension officers — distribution channel exists
- Crop.health API is paid; passes through as a cost of disease scans — implies a paid tier
- **Risk:** existing Kenyan ag-tech apps (DigiCow, iCow, mychama, etc.) compete for farmer attention. Crops differentiator is the SHEP-grounded season engine + market-led prompts. Defensible.
- **Risk:** Shira is currently livestock-led. Pivoting to "and crops" risks brand confusion. Plan should specify: is Shira repositioning to "all-farm OS" or staying "livestock + (small) crops add-on"?

### Revenue Readiness (Score: 4/10)

| Signal | Status |
|---|---|
| Pricing tier for crops decided | **No** (deferred above) |
| M-Pesa flow for crops-specific upgrade | Inherits Shira's; works |
| Variable cost model (Crop.health API) | Not yet ceilinged. **Need:** "max KES 50/month per farmer in disease scans, alert if exceeded." |
| Path to profitability per crops user | **Not modelled.** At KES 200/mo and ~KES 5/scan, breakeven is fragile if a farmer scans 10x/month. |
| LTV / CAC | **Unknown** — Shira's overall numbers not yet established. |

### Competitive Position (Score: 7/10)

- DigiCow: dairy-focused, doesn't touch crops. Direct lane open.
- iCow: SMS-based, not app-based. Different farmer segment.
- KALRO Cropmate: research project, not a commercial product. Could become a partner.
- DigiFarm (Safaricom): tried + closed (2023). Cautionary tale — distribution alone doesn't save bad UX.
- **Differentiator:** the SHEP-grounded "grow to sell" framing + the season engine are genuine moats *if* the JICA partnership is real. Without it, just another farm app.

### Investor Overall: 6.0/10

Plan is plausible commercially. Pricing and unit-economic gaps are P0-blockers if you want investor money; not blockers for the build itself.

---

## 6. Public Agent (Ethics / AI Boundary)

### Transparency (Score: 7/10)

| Signal | Assessment |
|---|---|
| Disease AI confidence scores | Plan: returns confidence + low-confidence warning at <70%. Good. |
| "Was this correct?" feedback | Plan: yes. Good. |
| Treatment recommendations | Plan: PCPB-registered only. Good (regulatory compliance). |
| Off-season prompts ("plant in March to hit June peak") | **Risk:** if every farmer plants in March because the app says so, the price won't peak. **The recommendation engine, applied at scale, undermines the very signal it relies on.** Document this paradox in the spec. |

### Data Privacy (Score: 6/10)

| Signal | Status |
|---|---|
| DPA 2019 (Kenya) consent for image storage | Plan: farmer consent flag. **Adequate** but the ToS / consent gate work in CLAUDE.md is still pending review by lawyer. Crops *cannot* launch until that lands. |
| Image data retention | Not specified. **Recommend:** 24-month retention then delete or anonymise (strip GPS). |
| Disease photos as training data | Plan: opt-in by default? Opt-out by default? **Decide here. Recommend: opt-in with clear explanation.** |
| Cross-backend data sharing (Django → Laravel sync) | Plan: nightly farm sync. **Privacy review needed:** what fields cross? Does the user know? |

### Human-AI Balance (Score: 8/10)

- Disease AI is advisory, not authoritative. Confidence scores + low-confidence warnings respect the farmer's judgment.
- Season Engine is a recommendation, not a mandate. Farmer can override any activity date.
- Off-season prompts are framed as opportunities, not instructions.
- Filament admin requires human sign-off for content changes. Good guardrail.

### Public Agent Overall: 7.0/10

Stronger than Shira's overall position because crops carries explicit consent + confidence + advisory-not-authoritative framing in the spec. Image consent + retention + opt-in/opt-out for training data still need locking in.

---

## Overall Governance Summary

| Dimension | Score | Notes |
|---|---|---|
| Ghost User | 5.75/10 | UX-shape right, content & trust gaps |
| Ghost Creator | 5.5/10 | Filament editor recommendation lifts to ~7 if accepted |
| Stack Optimiser | 7.0/10 | Laravel defensible if P0.5 happens |
| Admin Agent | 4.7/10 | Monitoring & ops runbooks implicit, must be explicit |
| Investor | 6.0/10 | Pricing + unit econ gaps |
| Public | 7.0/10 | Strong framing, image-data policies need locking |

### **Combined Score: 6.0/10**

**Below the 6.5 gate.** Per the plan, this means **remediate before P1 starts.** Remediations are well-scoped:

### Top 5 Priorities (ranked by impact)

1. **Hard prerequisite — JICA / KALRO partnership.** Without a signed letter or paid contracted agronomist, the "grounded in JICA SHEP PLUS" claim is hollow and content authoring has no domain authority. This single item gates P1.
2. **Add Filament-based agronomist editor + sign-off workflow to P1 scope** (~+2 days). Lifts Ghost Creator from 5.5 → 7.0 and makes P7 content authoring tractable.
3. **Add explicit monitoring deliverable to P1:** Sentry + Horizon dashboard exposed + Crop.health failure-rate alert + cross-backend sync alert. Lifts Admin from 4.7 → 6.5.
4. **Pad P1–P3 timelines by 20%** to absorb Laravel learning cost. The plan currently estimates ~3.5 weeks for P1+P2+P3; realistic is ~4.2 weeks.
5. **Decide pricing tier model** (crops included in existing tiers vs separate add-on) before P5 frontend work, so the upgrade modal can be designed correctly. Defer to a separate ~1-day pricing analysis owned by Joshua.

### Two open ethical/policy items to resolve before P5

- **Image data retention + opt-in vs opt-out for training data.** Recommend: opt-in by default; retain 24 months; explicit per-photo consent at scan time.
- **Off-season-prompt-at-scale paradox.** Acknowledge in spec. Mitigate: add a "regional capacity warning" if too many farmers in the same county plant the same crop in the same window.

### Hard prerequisites before P1 (re-stated)

| # | Item | Status | Owner |
|---|---|---|---|
| 1 | JICA / KALRO partnership | **NOT STARTED** | Joshua |
| 2 | Agronomist named (paid or partner) | **NOT NAMED** | Joshua |
| 3 | Native SW translator named | **NOT NAMED** | Joshua |
| 4 | Crop.health account + cost ceiling | **NOT DECIDED** (mock for P1–P4 acceptable) | Joshua |
| 5 | DPA 2019 ToS + consent gate (carry-over from CLAUDE.md) | **PENDING LAWYER REVIEW** | Joshua |
| 6 | Pricing tier model decided | **NOT DECIDED** | Joshua |
| 7 | UWC Laravel skill bootstrap (P0.5) | **NOT STARTED** | UWC (Joshua + Claude) |

### Comparison to Shira's 2026-04-15 governance pass

Shira's combined: **6.7/10** (above the 6.5 gate at the time — proceed with caveats).
Crops pre-build: **6.0/10** — below the gate.

The 0.7-point delta is almost entirely **content + partnership risk** (Ghost Creator + Ghost User + Investor). The engineering plan itself is solid (Stack Optimiser 7.0). Crops is *not* a stack problem; it's a **domain partnership and content labour** problem.

### Recommendation

**Do not start P1 yet.** Spend 1–2 weeks on prerequisites #1 (JICA/KALRO partnership conversation) and #6 (pricing tier decision). In parallel, **start P0.5 immediately** — Laravel skill bootstrap is independent of the content/partnership work and de-risks the engineering side. After 1–2 weeks, re-run this governance pass; if combined score crosses 6.5/10, P1 starts.

If JICA/KALRO partnership is genuinely unobtainable, the plan should pivot to:
- "Inspired by SHEP PLUS" framing instead of "grounded in JICA SHEP PLUS" — softer marketing claim, removes the dependency
- Hire a freelance agronomist with KALRO credentials for content authoring
- Acceptable but Investor + Ghost User scores drop ~1 point each

---

*Generated by UWC Agent Governance System (simulated) on 2026-05-07. Re-run after prerequisites #1 and #6 are resolved.*

---

## Addendum v1.1 — Same-day decision deltas (2026-05-07)

After v1.0 was issued, Joshua resolved 4 of the 7 hard prerequisites and adopted all 5 governance recommendations into the plan. Re-scoring on the strength of those decisions:

### Decisions taken (same day)

| # | Item | Resolution |
|---|---|---|
| 1 | JICA / KALRO partnership | **Deferred** — pivot to "inspired by SHEP PLUS" framing. Pursue formal partnership only if pilot traction justifies. |
| 2 | Agronomist named | **Sourcing freelance KALRO-credentialed contractor** (named TBD; named-by-when not yet committed) |
| 3 | Native SW translator | Sourcing in parallel with #2 |
| 4 | Crop.health account | **Mock for P1–P4, real at P5 with monthly ceiling** — variable cost ringfenced |
| 5 | DPA 2019 ToS / consent gate | Still pending lawyer review (carry-over) — **not crops-specific blocker** since Shira itself blocks on this |
| 6 | Pricing tier model | **Deferred** — will adjust as project progresses |
| 7 | UWC Laravel skill bootstrap (P0.5) | **Starting today** — independent of partnership work |

### Plan changes adopted

- **P1 scope expanded** to include Filament agronomist editor + sign-off workflow (Ghost Creator concern) and explicit monitoring deliverable: Sentry + Horizon dashboard + Crop.health failure alerts + sync alerts (Admin Agent concern). P1 timeline grew from 1.5 → 2 weeks.
- **P1–P3 padded by 20%** to absorb Laravel-learning cost (Stack Optimiser concern).
- **Marketing claim softened** from "grounded in JICA SHEP PLUS" to "inspired by SHEP PLUS" — removes partnership dependency from Day 1 launch (Ghost User + Investor concern).
- **Two ethics items deferred** with documented working assumptions (24-month image retention, opt-in for training data) — Public Agent flags accepted as "adjust as project goes" rather than locked now.

### Revised scoring (post-decisions)

| Dimension | v1.0 | v1.1 | Delta | Reason |
|---|---|---|---|---|
| Ghost User | 5.75 | 6.5 | +0.75 | "Inspired by SHEP PLUS" framing removes the trust-claim risk; phased Crop.health (mock→real) keeps disease feature usable from P5; SW translator sourcing acknowledged |
| Ghost Creator | 5.5 | 7.0 | +1.5 | Filament editor + sign-off workflow adopted into P1 — content authoring becomes tractable for non-engineer agronomist |
| Stack Optimiser | 7.0 | 7.5 | +0.5 | 20% timeline padding on P1–P3 acknowledges Laravel learning cost; P0.5 starting today de-risks |
| Admin Agent | 4.7 | 6.5 | +1.8 | Monitoring deliverable now explicit in P1 (Sentry, Horizon, Crop.health alerts, sync alerts) — biggest single delta in this report |
| Investor | 6.0 | 6.0 | 0 | Pricing decision deferred per user direction; revisit before P5 |
| Public | 7.0 | 6.8 | -0.2 | Slight downgrade — image retention + opt-in were flagged as "lock now" but user chose defer; working assumptions documented as mitigation |

### **Revised Combined Score: 6.7/10** — *clears the 6.5 gate*

Same score as Shira's 2026-04-15 baseline (6.7/10). P1 may proceed in parallel with P0.5 once Laravel skill bootstrap is at least 50% complete (estimated 3–4 days of skill authoring before the bootstrap is solid enough to sustain P1 build velocity).

### Remaining hard gates (will block specific phases, not P0.5)

- **Agronomist + SW translator named** — gates **P7 content authoring**, not P1. Acceptable to defer naming by ~4 weeks.
- **Pricing tier model** — gates **P5 frontend** (upgrade modal can't be designed without it). ~6 weeks runway.
- **Real Crop.health credentials** — gates **P5 wiring**, not P1–P4 (mock works). ~6 weeks runway.
- **Shira PWA out of maintenance mode** — gates **P5 launch** (PWA needs to host the new feature folder). Independent of crops work.

### Single highest-impact unresolved risk

The **Shira PWA itself is in maintenance mode** at the time of writing (per CLAUDE.md "Current state: ON"). No matter how good the crops module is, it cannot launch into a PWA that farmers cannot reach. **Recommendation:** before P5 of crops starts, run a small sub-project to clear `app.shira.farm` maintenance — a separate concern that the crops governance report flags but does not own.

---

*Addendum v1.1 issued 2026-05-07 same-day after v1.0. Next governance pass: after P0.5 (Laravel skills) is complete, before P1 starts.*

---

## Addendum v1.2 — Post-skills + plan-additions re-score (2026-05-07, end-of-day)

After v1.1, two engineering investments completed on the same day:

1. **All 14 Laravel skill files drafted** in `~/Desktop/uwc-web-co/00-skills/app-build/laravel/` (P0.5 complete).
2. **Plan amendments** absorbing all in-control governance lifts:
   - P5: explicit Swahili translation pipeline + `/lint:i18n` CI script + in-product help (HelpTooltip + WelcomeModal pattern reused from Shira)
   - P8: pilot success/failure thresholds quoted as numbers (7 metrics, threshold-to-scale + threshold-to-stop), 6 runbooks named, weekly scheduled governance re-runs

### What got better

| Dimension | v1.1 | v1.2 | Delta | Reason |
|---|---|---|---|---|
| Ghost User | 6.5 | 7.0 | +0.5 | SW translation pipeline + in-product help committed in P5; CI gate prevents English-only strings shipping; reuses Shira's proven HelpTooltip pattern |
| Ghost Creator | 7.0 | 7.0 | 0 | No movement — agronomist still not named (Joshua's call) |
| Stack Optimiser | 7.5 | 8.0 | +0.5 | All 14 Laravel skills exist (not a subset); skills index README documents discipline rules + maps to Django equivalents; per-customer storage toggle has its own dedicated skill |
| Admin | 6.5 | 7.5 | +1.0 | Pilot thresholds quoted as numbers (7 metrics with explicit scale/stop bands); 6 runbooks named in P8; weekly governance re-runs scheduled |
| Investor | 6.0 | 6.0 | 0 | No movement — pricing tier still deferred (Joshua's call) |
| Public | 6.8 | 6.8 | 0 | No movement — image retention + opt-in deferred (per user direction) |

### **Revised Combined Score: 7.05/10**

The ceiling I committed to in this morning's prediction. To cross 8.0/10 the path is exactly what was forecast:

| To reach | Joshua decides | Ghost Creator | Investor | Public | Combined estimate |
|---|---|---|---|---|---|
| 7.5 | Name agronomist (Ghost Creator → 8.0) | +1.0 | — | — | ~7.22 |
| 7.7 | + Decide pricing tier (Investor → 7.5) | — | +1.5 | — | ~7.47 |
| 8.0 | + Lock image retention + opt-in defaults (Public → 8.0) | — | — | +1.2 | ~7.67 |
| 8.2 | + Clear Shira PWA maintenance mode (Ghost User → 8.0) | — | — | — | ~7.85 |

So even with all 4 of Joshua's outstanding decisions, the realistic ceiling is **~7.85/10** in this iteration — not 8.0. Honest read: getting to a flat 8.0 would require either pilot data (which doesn't exist yet) or shipping in-product help that's been A/B-tested (which is post-launch). **Pre-launch, ~7.85 is the honest ceiling.** Calling it "8.0/10" before any farmer touches the product would be theatre.

### What this score lets P1 actually do

**7.05/10 clears the 6.5 P0 gate by a comfortable margin** (the same morgin Shira's 6.7 had at its launch baseline). All the engineering preconditions for P1 are now in place:

- Laravel skill set complete (P0.5 done)
- Filament agronomist editor + sign-off in P1 scope
- Monitoring deliverable explicit in P1 (Sentry + Horizon + alerts)
- 20% timeline padding on P1–P3 acknowledged
- "Inspired by SHEP PLUS" framing accepted (no JICA partnership dependency)
- Pilot thresholds quoted (7 metrics)
- 6 runbooks named for P8
- Weekly governance re-runs scheduled

### Remaining gates between **here** and **P1 kickoff**

| # | Item | Status | Blocking? |
|---|---|---|---|
| 1 | Agronomist named (paid freelance, KALRO-credentialed) | NOT NAMED | **Blocks P7 content authoring**, not P1 |
| 2 | SW translator named | NOT NAMED | **Blocks P7**, not P1 |
| 3 | Pricing tier model decided | NOT DECIDED | **Blocks P5 upgrade-modal design**, not P1 |
| 4 | Crop.health real credentials | DEFERRED to P5 | Mock works for P1–P4 |
| 5 | DPA 2019 ToS / consent gate | PENDING LAWYER (Shira-wide) | **Blocks pilot launch (P8)**, not P1 |

**Verdict on P1 kickoff:** **GO.** No gates block the P1 backend skeleton. The 5 remaining items are P5-onwards prerequisites with 6+ weeks of runway.

### Single highest-impact unresolved risk (carried from v1.1)

The **Shira PWA itself remains in maintenance mode**. Crops cannot launch into a PWA farmers cannot reach. Recommendation unchanged: launch a sub-project to clear `app.shira.farm` maintenance before P5 completes.

---

*Addendum v1.2 issued 2026-05-07 end-of-day after Laravel skill bootstrap + plan additions. Next governance pass: weekly during pilot (P8 onwards).*

---

## Addendum v1.3 — Panda standalone rebrand (2026-05-07)

**Decision recorded:** the project is renamed **Panda** and built **standalone** — not as a Shira module. This was Joshua's call after reviewing v1.2:

> "It is better to do the actual development using the pipeline, that way we are following a proper SDLC + best practice. All the asks can be done later in the project. We can separate this and call it Panda, as they are at a different progress stage and will be standalone compared to the original and official Shira. Shira main should clear of the maintenance mode for sure. Kick off."

### What changed structurally

| Area | Before (Shira Crops module) | After (Panda standalone) |
|---|---|---|
| Backend integration | JWT-share with Shira via skill-laravel-sanctum-shared-jwt | Own JWT issued by Panda's own Sanctum |
| Database | Separate Postgres DB on shared instance, with farm-mirror sync from Shira | Own Postgres DB, own users, no Shira sync |
| Repo | `farmcore/` (subtree) or sibling | New repo at `~/Desktop/panda/` |
| URL | `shira.farm/crops`, `app.shira.farm/crops`, `api-crops.shira.farm` | `panda.farm`, `app.panda.farm`, `api.panda.farm` |
| Auth UX | Same login as Shira (one farmer, one account) | Own farmer accounts, own onboarding |
| Distribution | Inherits Shira's existing farmers | Standalone go-to-market — county officers, KALRO, paid acquisition |
| Brand | "Shira Crops" / "Shira Horticulture" module | Standalone product — own brand identity TBD |
| Marketing site | Add pages to existing shira.farm Astro site | Own Astro site at panda.farm |
| Pilot | Could leverage existing Shira pilot farmers | Fresh 200-farmer pilot in Meru + Kirinyaga |

### Re-scoring on the rebrand

| Dimension | v1.2 | v1.3 | Delta | Reason |
|---|---|---|---|---|
| Ghost User | 7.0 | 6.5 | -0.5 | Loses Shira's existing farmer base as discovery channel; standalone product means "Mwende must hear about Panda separately, install separately, register separately." Mitigation: same in-product help + SW pipeline still in plan. |
| Ghost Creator | 7.0 | 7.0 | 0 | Filament editor + sign-off workflow unchanged. Agronomist still not named (deferred per user). |
| Stack Optimiser | 8.0 | 8.5 | +0.5 | **Integration risk evaporates.** Cross-backend JWT rotation, mirror-row drift, internal API surface, sync alerting — all gone. Cleaner architecture. The shared-JWT skill stays in the portfolio for future projects, just not load-bearing for Panda. |
| Admin | 7.5 | 7.5 | 0 | Pilot thresholds, runbooks, weekly governance re-runs unchanged. |
| Investor | 6.0 | 5.5 | -0.5 | **Loses Shira-distribution unfair advantage.** Standalone means cold-start go-to-market — county officers + KALRO + paid acquisition. More expensive CAC, longer time to product-market fit. Compensating: cleaner story for any future investor / acquirer (no entanglement with Shira). |
| Public | 6.8 | 6.8 | 0 | Image retention + opt-in deferred per user direction; unchanged. |

### **Revised Combined Score: 6.97/10** (essentially flat from v1.2's 7.05)

The Stack Optimiser lift roughly cancels the Ghost User + Investor drops. Net: same score, materially different shape. Standalone Panda trades **integration complexity** for **distribution complexity**. Both are real costs.

### Honest read

The standalone decision is defensible *if* Panda's go-to-market plan in P8 names a concrete distribution channel (county extension officer partnership, KALRO collaboration, or paid acquisition budget). If P8 lands without that, the Investor score drops further at the post-launch governance re-run.

Risk **transferred**, not eliminated: from "what if Shira/Panda integration breaks?" to "what if Panda has zero farmers because nobody knows about it?"

### What this changes about the plan

- Plan section "Integration boundary (Django ↔ Laravel)" → mostly delete; auth is standalone
- Plan section "URL structure" → rewrite to `panda.farm` / `app.panda.farm` / `api.panda.farm`
- Plan P5 frontend → not a feature folder of Shira's PWA; it's Panda's *own* PWA
- Plan P6 marketing → Panda's *own* Astro site, not pages added to Shira's
- The `skill-laravel-sanctum-shared-jwt.md` is documented in the portfolio for future use but not invoked by Panda
- The `~/Desktop/panda/` skeleton (README, CLAUDE, PROJECT_STATE, MASTER_PROMPT) replaces "subtree under farmcore" plan

### What stays the same

- 14 Laravel skill files — all still apply
- Filament agronomist editor + sign-off workflow — unchanged
- Pilot thresholds + runbooks + weekly governance re-runs — unchanged
- "Inspired by SHEP PLUS" framing — unchanged
- Crop.health mock-then-real progression — unchanged
- 17 crops Phase 1 scope — unchanged
- Storage R2/S3 toggle — unchanged

### Outside-Panda items noted

- **Shira PWA out of maintenance mode** — Joshua explicitly committed to this as a separate Shira sub-project. Removed from Panda's risk list (was carried as a launch dependency in v1.1 + v1.2; now correctly scoped out).

### **P1 kickoff status: GO**

Pipeline begins. UWC's Claude Code agent (in a new session at `~/Desktop/panda/`) pastes `docs/MASTER_PROMPT.md`, invokes `/new-app panda`, and the Laravel skeleton begins materialising. Joshua's role from here is decisions (agronomist, pricing, etc.) — not coding.

---

*Addendum v1.3 issued 2026-05-07 — Panda standalone rebrand. Next governance pass: weekly from P8 (pilot launch) onwards.*
## Addendum v1.4 — Independent second-opinion review (2026-05-07)

This addendum is an *external* read of v1.0–v1.3, written by a different evaluator (Claude Opus 4.7 [1M], invoked from the Shira session, not the Panda session). It does not own a decision; it surfaces what I think is mispriced or unaddressed in the existing scoring, and what I would want answered before sustained capital is committed to Panda.

### What v1.0–v1.3 got right

- **Engineering plan is solid.** Laravel + Filament + Horizon is a defensible stack for this domain. The 14 skill files are real and competent. The "Inspired by SHEP PLUS" framing is the right de-risking move once partnership signing turned uncertain.
- **20% timeline padding** on P1–P3 is honest. First Laravel project at 14-week scope without padding would have been the silent killer.
- **Filament editor + sign-off workflow** lift on Ghost Creator (5.5 → 7.0) is correctly priced. Forcing agronomists to write JSON PRs would have been the brittle path.
- **Honest self-criticism** of the score in v1.2 ("calling it 8.0/10 before any farmer touches the product would be theatre") is exactly the kind of self-awareness that distinguishes a real governance pass from a ratification ceremony.

### What I think is mispriced

#### 1. v1.3 standalone rebrand — Ghost User and Investor drops should be larger

v1.3 took **-0.5 on Ghost User** (loss of cross-sell discovery) and **-0.5 on Investor** (loss of Shira distribution). I think both should be at least -1.0.

The v1.0–v1.2 scoring assumed Mwende-the-dairy-farmer sees a sidebar entry the day Shira flips `crops_enabled`. That is a near-zero-CAC discovery channel. Standalone Panda has to acquire Mwende from cold — county officer training material, KALRO partnership conversations, paid acquisition, or word-of-mouth that doesn't exist yet. The CAC delta between "existing farmer sees a new tab" and "smallholder farmer hears about a new app for the first time" is not 0.5 points of Ghost User score; it is closer to a full point.

Conservative re-score on v1.3:

| Dimension | v1.3 | Conservative | Reason |
|---|---|---|---|
| Ghost User | 6.5 | **5.5** | Cold-start discovery is materially harder, not slightly harder. Independent installation, independent registration, independent learning curve all add friction. |
| Investor | 5.5 | **4.5** | CAC is the single biggest risk to a farmer-app's unit economics. Without a named distribution channel, the path to LTV > 3× CAC is conjecture. |

If those two re-scores hold, **revised combined = ~6.30/10 — *just below* the 6.5 gate**.

This does not necessarily mean "don't kick off." It means the kick-off justification rests on the engineering side (Stack Optimiser 8.5, Admin 7.5) carrying the product side (User 5.5, Investor 4.5). That is a reasonable bet for a build-first founder, but the scoring should make the bet visible rather than smooth it over with smaller deltas.

#### 2. The score-gate became decorative on v1.3

Reading v1.3 chronologically: Joshua's directive ("Kick off") arrives as the *premise* of v1.3, and v1.3's score then arrives at 6.97 — comfortably above the gate. There is no version of this report where v1.3 scored below 6.5 and the kick-off was rolled back. The gate did its job from v1.0 (held the line at 6.0/10 → forced remediation) through v1.2 (cleared at 7.05). On v1.3 it became a justification engine.

This isn't unique to Panda — every multi-iteration governance pass on the same day with the same evaluator drifts toward this. The remediation is procedural: **the v1.3 score should have been computed and signed off by a different evaluator** (or at least at a different sitting, with an explicit "what would push this below the gate?" pre-mortem). Going forward, the *next* governance pass — weekly during pilot — should be done in a fresh session against the live system and live numbers, not against the plan as remembered.

#### 3. Path to first 200 pilot farmers is unaddressed

The pilot success thresholds are well-defined (NPS > 40, daily active > 50%, willingness to pay > 30%). The path to *recruiting* the 200 farmers who will produce those numbers is not in the plan. v1.3 mentions "county extension officers + KALRO + paid acquisition" as a comma-separated list, not a plan.

Specific questions Joshua should answer in the first 4 weeks of P1 (parallel to engineering work, before P5 frontend builds the upgrade modal):

- **Who is the named distribution partner?** "County officers in Meru" is not a name. "Mr. X, sub-county agricultural officer in Buuri ward, has agreed to convene 30 farmers for an introductory session on date Y" is a name.
- **What is the cost per pilot farmer acquired?** Even a rough "we expect KES Z per farmer" is enough to size whether the pilot is fundable. KES 0 (pure word of mouth) is not realistic for cold-start.
- **What is the gating event for "kill the pilot"?** The thresholds say <30% logging > 3 activities in 60 days = stop. Who decides? Who is empowered to call it? On which date?

These are not P1 engineering blockers. They are P1 founder-attention blockers — work Joshua should be doing while the agent builds.

### What I do not push back on

- **The Laravel rebuild from scratch.** Yes, "rewrite Shira's logic in Laravel" is duplicate work, but the deliberate skill investment + Filament + standalone-product reasoning hold up.
- **Deferring agronomist + pricing.** Reasonable for P1 backend skeleton. Becomes critical at P5/P7.
- **Deferring the JICA partnership.** "Inspired by SHEP PLUS" is the right framing if the partnership is ≥ 6 weeks away.
- **Same-day rebrand of Crops → Panda.** The integration-cost-vs-distribution-cost trade is real; choosing the trade you can attack with engineering (cleaner standalone) over the trade you cannot (forced-marriage UX) is correct.

### What I would want answered before sustained capital is committed

| # | Question | Latest answer can land |
|---|---|---|
| 1 | Named distribution partner (e.g. specific county officer, specific KALRO contact, or budgeted paid-acquisition channel)? | End of P1 (week 2) |
| 2 | Cost per pilot farmer estimate + total pilot acquisition budget? | End of P1 (week 2) |
| 3 | Who has authority to kill the pilot, on which date, against which thresholds? | Before P8 starts (week 12) |
| 4 | If the JICA / KALRO partnership lands during P3–P5, what changes in the plan? (Dependency injection point, not just re-marketing) | When/if partnership signs |
| 5 | What is the explicit relationship between Shira and Panda — separate companies, sister products under UWC, future merger? | Before P6 marketing site goes public |

### Revised independent score (for the record)

| Dimension | v1.3 | v1.4 (this) | Reason |
|---|---|---|---|
| Ghost User | 6.5 | **5.5** | Cold-start discovery materially under-priced |
| Ghost Creator | 7.0 | **7.0** | Unchanged — Filament workflow holds up |
| Stack Optimiser | 8.5 | **8.5** | Unchanged — engineering plan is solid |
| Admin | 7.5 | **7.5** | Unchanged — runbooks named, thresholds quoted |
| Investor | 5.5 | **4.5** | No named distribution partner = CAC risk under-priced |
| Public | 6.8 | **6.8** | Unchanged — image retention deferred, working assumption acceptable |

### **Independent revised combined: 6.63/10**

Still clears the 6.5 gate, but only by 0.13. This is much closer to the wire than v1.3 suggested. The kick-off recommendation holds, but the slack is thinner than the v1.3 score implies.

### Independent verdict

**Kick-off is defensible** because the engineering plan is solid and the build is independent of the unresolved business questions for at least the first 4 weeks. **The kick-off is not "comfortable"** — Investor 4.5 / Ghost User 5.5 are the worst dimensions and they are the dimensions that determine whether Panda is a product or a hobby. Joshua's founder-attention bandwidth in the first 4 weeks should split: roughly 30% engineering review of agent output, 70% on questions #1, #2, and #5 above. If the answers to #1 and #2 are still "TBD" 4 weeks in, pause P5.

The score gate is now under suspicion. I would **not** trust an internal v1.5 produced in this same session at this same date as governing P5 launch. The next gate should be a fresh-session governance pass against the working P1+P2+P3 codebase, with Joshua answering questions #1–#5 at the same sitting, before P5 frontend work begins.

---

*Addendum v1.4 issued 2026-05-07 — independent second-opinion review from the Shira session. This addendum does not change the kick-off decision; it surfaces what was mispriced or unaddressed and lists the 5 questions to answer in the first 4 weeks.*
