# Bottlenecks & Solutions — Panda (Shira Crops)

**Issues that blocked progress and how they were resolved.**

> Add blockers as they occur. Update status when resolved.

---

## Development Environment

### (none yet — populate as encountered)

---

## Architecture Decisions That Prevented Rework

| Decision | Rationale |
|---|---|
| Laravel (not Django) | JAICA spec was already Node+Postgres; Laravel gave PHP ecosystem + Filament admin for free |
| spatie/laravel-multitenancy | Mature package, same row-level isolation pattern proven in Shira + MyChama |
| brick/money | Mandatory for all KES fields — same discipline as Django DecimalField |
| Filament admin | Agronomist content editor — role-gated, Cloudflare Access IP allowlist |
| React PWA (same as Shira) | Shared frontend expertise across products |
| Separate Hetzner stack | Shared CX22 box but separate Docker compose — isolation without new VPS cost |

---

*Last updated: 2026-05-16 — stub created per BUSINESS.md §5 scaffold requirement.*
