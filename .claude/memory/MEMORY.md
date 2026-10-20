# Panda (Shira Crops) — Auto Memory

## Project Location
- Root: `~/Desktop/panda`
- Backend: Laravel 11, PHP 8.3, Postgres 16
- Frontend: `pwa/` (React 19, Vite 7, TypeScript 5.9)
- Marketing: `marketing/` (Astro 5, Cloudflare Pages)

## Key Files
- `CLAUDE.md` — architecture rules, read every session
- `PROJECT_STATE.md` — deliverable tracker
- `LESSONS.md` — surface-level index (BUSINESS.md §5 compliant)
- `.claude/memory/debugging_lessons.md` — bug catalog (PANDA-NNN)
- `.claude/memory/implementation_notes.md` — creation order, patterns
- `.claude/memory/bottlenecks_solutions.md` — blockers, architecture evolution

## Stack (exact versions)
```
Backend: PHP 8.3, Laravel 11.x, Postgres 16, Redis 7, Horizon 5.30+, Filament 3.2+, Sanctum 4.0+, Pest 3.x, brick/money 0.10+, spatie/laravel-multitenancy 4.0+
Frontend: Node 20, React 19, TypeScript 5.9, Vite 7, TanStack Query 5, react-hook-form 7, zod 3, Tailwind CSS 4, shadcn/ui (new-york)
Marketing: Astro 5.x, Tailwind CSS 4, Cloudflare Pages
Hosting: Hetzner CX22 (shared with Shira), Cloudflare Pages
```

## Current State
- **P1–P5:** Initial build complete — multi-tenancy, CI, Docker, core models, PWA scaffold
- **Bug count:** 3 (PANDA-001 through PANDA-003)
- **Skills exercised:** 14 Laravel skill files

## UWC Skills (from ~/Desktop/uwc-web-co/00-skills/app-build/laravel/)
| Skill | When | Trigger |
|-------|------|---------|
| skill-laravel-eloquent-model.md | New model | new model |
| skill-laravel-multitenancy.md | Tenant scoping | tenant |
| skill-laravel-form-request.md | Validation | new endpoint |
| skill-laravel-resource.md | API response | new endpoint |
| skill-laravel-controller.md | Route handler | new endpoint |
| skill-laravel-observer.md | Single-model side-effects | observer |
| skill-laravel-event-listener.md | Cross-cutting side-effects | event |
| skill-laravel-money-decimal.md | Any monetary field | money, KES |
| skill-laravel-storage-toggle.md | Storage config | storage |
| skill-laravel-pest-test.md | Testing | test |

## Demo Data
- (populate as seed commands are added)

---

*Last updated: 2026-05-16 — stub created per BUSINESS.md §5 scaffold requirement.*
