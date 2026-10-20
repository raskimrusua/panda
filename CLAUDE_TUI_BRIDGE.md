# CLAUDE_TUI_BRIDGE.md — Panda
**Canonical source:** `~/Desktop/uwc-web-co/00-skills/build/skill-tui-session.md` (v1.0)
**This file:** Project-specific keyword triggers and memory file mappings. Portfolio-level triggers are in the canonical skill.

## Project-Specific Keyword Triggers

| Trigger words | Rule / Memory to load | Action |
|--------------|----------------------|--------|
| laravel, eloquent, artisan, php, composer | uwc-web-co/00-skills/app-build/laravel/ skill files | Load relevant Laravel skill |
| crop, season, farm, harvest, planting, activity | Panda domain models. Season Engine. | Load domain context |
| multitenant, tenant, farm_id, user_id | laravel-multitenancy skill. Row-level isolation. | Verify tenant scope |
| pest, test, phpunit, feature test, unit test | laravel-pest-test skill | Load test patterns |
| migration, schema, table | laravel-migration skill. Safe migration rules. | Verify safe |
| validation, form request, request | laravel-form-request skill | Load validation patterns |
| controller, route, endpoint, API | laravel-controller + laravel-resource skills | Load API patterns |
| money, price, cost, decimal, payment | laravel-money-decimal skill. Casts. | Verify decimal |
| observer, event, listener, side-effect | laravel-observer + laravel-event-listener skills | Load side-effect patterns |
| storage, file, upload, S3, R2 | laravel-storage-toggle skill | Load storage patterns |
| MASTER_PROMPT.md, JAICA | docs/MASTER_PROMPT.md. Seed prompt for Claude Code sessions. | Reference but TUI uses this bridge |
| PROJECT_STATE.md | Current status, deliverable queue. | Read before acting |

## Project Memory File Map

| Domain | Files |
|--------|-------|
| Laravel skills | uwc-web-co/00-skills/app-build/laravel/ (portfolio-level) |
| Architecture | .claude/memory/MEMORY.md |
| Debugging | .claude/memory/debugging_lessons.md |
| Blockers | .claude/memory/bottlenecks_solutions.md |
| Patterns | .claude/memory/implementation_notes.md |
| Any code action | .claude/memory/debugging_lessons.md (always) |

## Project Post-Write Commands
- PHP syntax: `php -l <file>`
- Laravel: `php artisan config:clear && php artisan route:list`
- Pest tests: `php artisan test --parallel`
