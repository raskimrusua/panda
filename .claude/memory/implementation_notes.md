# Implementation Notes — Panda (Shira Crops)

**File creation order, repeated processes, patterns discovered during the build.**

> Populate as patterns emerge. This file grows with every milestone.

---

## File Creation Order (Laravel — per module)

```
1. Migration           — database schema
2. Model               — Eloquent model with relationships
3. FormRequest         — validation rules
4. Resource            — API response transformation
5. Controller          — route handlers
6. Observer/Listener   — side-effects
7. Route registration  — api.php
8. Pest tests          — feature + unit
```

## File Creation Order (PWA Frontend — per module)

```
1. src/types/[module].ts           — TypeScript types
2. src/api/[module].ts             — API functions
3. src/hooks/use[Module].ts        — React Query hooks
4. src/components/forms/[Entity]Form.tsx — forms with zodResolver
5. src/pages/[Module]Page.tsx      — page component
```

## Repeated Processes (track when a process is done 3+ times)

| Process | Frequency | Skill candidate? |
|---------|-----------|-----------------|
| (none yet) | — | — |

## Patterns Discovered

### Laravel Patterns
- (populate as patterns emerge)

### Frontend Patterns
- (populate as patterns emerge)

---

*Last updated: 2026-05-16 — stub created per BUSINESS.md §5 scaffold requirement.*
