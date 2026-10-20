# Panda — P8 Hardening Scope

**Status:** P5/P6 complete (MVP). P8 = pilot readiness + flip-to-live.
**Timing:** Items below are queued for the pilot readiness milestone, not blocking current work.

---

## P8 Checklist (from P0 governance report gaps + production readiness audit)

### Security Hardening (4 items)

| # | Item | Effort | Notes |
|---|---|---|---|
| P8.1 | **Security headers middleware** | 1 hour | Laravel's `SecureHeaders` or manual middleware — CSP, HSTS, X-Frame-Options, X-Content-Type-Options. Currently running behind Hetzner nginx (which has some defaults) but no app-level enforcement |
| P8.2 | **Rate limiting** | 30 min | Laravel throttle middleware on auth routes (5/min login), API routes (60/min general). Currently using defaults; needs explicit per-route config |
| P8.3 | **Filament admin hardening** | 1 hour | Cloudflare Access IP allowlist in front of `/admin`. Currently role-gated but publicly reachable. Needs nginx + CF Access config |
| P8.4 | **CORS pinning** | 15 min | Lock `config/cors.php` `allowed_origins` to `panda-pwa.pages.dev` + `panda-marketing.pages.dev` in production. Currently may allow `*` in dev |

### Monitoring (3 items)

| # | Item | Effort | Notes |
|---|---|---|---|
| P8.5 | **UptimeRobot** | ✅ DONE (2026-10-20) | Monitors created: 804350912 `panda-api.shira.farm/up` + 804350915 `panda.shira.farm`; preview-URL monitors 804350265 `panda-marketing.pages.dev` + 804350267 `panda-pwa.pages.dev`. Alerts: useshiira@gmail.com. Custom-domain monitors report DOWN until the DNS/SSL flip executes (registry: uwc-agency-admin/docs/uptime-monitors.md) |
| P8.6 | **Disk/memory alerts** | 30 min | Hetzner CX22 shared box — need alert when disk <5GB or memory consistently >80%. Simple cron + email to Josh |
| P8.7 | **Horizon dashboard auth** | 15 min | `/horizon` dashboard needs auth gate (currently accessible to anyone who knows the URL) |

### Backup & Recovery (2 items)

| # | Item | Effort | Notes |
|---|---|---|---|
| P8.8 | **Database backup script** | 30 min | pg_dump to R2 or local disk, daily cron. Pattern already proven on Shira |
| P8.9 | **Backup verification** | 15 min | Add `pg_restore --list` check to backup script — verify backup is valid, not just that it ran |

### Domain & DNS (2 items)

| # | Item | Effort | Notes |
|---|---|---|---|
| P8.10 | **Wire panda.shira.farm DNS** | 30 min | Cloudflare DNS A record → Hetzner IP. CF proxy on. SSL via CF Origin cert (same pattern as api.shira.farm) |
| P8.11 | **Nginx vhost for panda.shira.farm** | 15 min | Separate server block on Hetzner nginx. Currently on port 8090 (test); needs port 443 public |

### Production Config (2 items)

| # | Item | Effort | Notes |
|---|---|---|---|
| P8.12 | **Env hardening** | 15 min | `APP_ENV=production`, `APP_DEBUG=false`, `SANCTUM_STATEFUL_DOMAINS=panda-pwa.pages.dev`. Verify no dev keys in `.env` |
| P8.13 | **Queue worker monitoring** | 15 min | Verify Horizon is running in production, queues are being consumed, failed jobs are logged. Currently tested on Hetzner but not monitored |

---

## Total Estimated Effort: ~6 hours

All items are configuration/ops — no code architecture changes. Most can be done by an operator on the server. The security headers middleware (P8.1) is the only code-level change.

---

## Order of Operations

1. P8.12 (env hardening) — do first, prevents dev settings in prod
2. P8.4 (CORS pinning) — prevents cross-origin leaks
3. P8.2 (rate limiting) — DOS protection before public exposure
4. P8.1 (security headers) — defense in depth
5. P8.10 + P8.11 (DNS + nginx) — flip to public
6. P8.3 (Filament CF Access) — lock admin before public
7. P8.7 (Horizon auth) — lock before public
8. P8.8 + P8.9 (backups) — protect data
9. P8.5 + P8.6 (monitoring) — visibility
10. P8.13 (queue monitoring) — last, ops comfort

---

*Scope prepared 2026-05-16. Ready for P8 implementation session.*
