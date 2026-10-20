# Debugging Lessons — Panda (Shira Crops)

**Bug catalog using the Problem → Root cause → Fix → Rule format.**
**Add new entries at the top of their category. ID format: PANDA-NNN**

> Panda is 11 days old (P0 governance report: 2026-05-07). This file will grow with every milestone.

---

## P1–P5 (Initial Build)

### PANDA-001: ActivityLog flaky test assertions
**Problem:** Test assertions on ActivityLog ordering failed intermittently in CI.
**Root cause:** Parallel test runs produced different insertion order. No explicit `orderBy('id')` on assertions.
**Fix:** Added `->orderBy('id')` to SeasonModelTest + TenantTest.
**Rule:** Pest assertions on ordered data must explicitly sort. Parallel runs shuffle insertion order.

### PANDA-002: PDF download returned JSON 401 from PWA
**Problem:** PWA PDF download requests hit API auth middleware and returned JSON 401 instead of PDF.
**Root cause:** PWA download URLs didn't carry auth tokens. The API auth middleware intercepted before the download handler.
**Fix:** Token-bearing URL pattern + auth middleware exemption for download routes.
**Rule:** PWA downloads need token-bearing URLs. Direct API access returns JSON errors, not files.

### PANDA-003: Horizon container crash — missing pcntl
**Problem:** Laravel Horizon worker container crashed silently with no clear error.
**Root cause:** `pcntl` PHP extension was not installed in the Dockerfile. Horizon requires it for process management.
**Fix:** Added `pcntl` to Dockerfile PHP extension install.
**Rule:** Horizon requires `pcntl` — verify in Dockerfile before first deploy.

---

*Last updated: 2026-05-16 — stub created per BUSINESS.md §5 scaffold requirement.*
