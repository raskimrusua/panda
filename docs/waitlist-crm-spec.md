# Phase-8 Waitlist CRM — Spec (panda_waitlist)

Prepared 2026-10-20 (panda-launch-track). Status: **spec — implementation + deploy only with Josh's approval.**

## Problem

`panda_waitlist` (D1 `panda-marketing-db`) receives signups, but there is no admin
surface. Working the pilot funnel today means raw D1 queries or the CF dashboard.
Shira has the full marketing-admin pattern (leads, tickets, board); Panda needs
only the waitlist slice of it for the pilot.

## Scope (minimal, single-table)

1. **List view** — all rows, newest first, with:
   - name, email, phone, county, crop, acreage
   - `status` badge (new / contacted / enrolled / declined / lost)
   - `source` (UTM attribution — pipe-joined string)
   - `created_at`
   - Filters: status, county, source prefix (e.g. `extension_officer`), text search on name/email
   - Counters per status (dashboard-ish header row)
2. **Status update** — change a row's status + optional `notes`, stamped `updated_at`.
3. **No CRUD beyond that.** Editing/creating/deleting rows is out of scope — the
   funnel only moves status forward. (If a bad row lands, D1 console is fine.)

## Implementation shape (mirrors Shira's admin pattern, scoped down)

- Route: `/admin/waitlist` (Astro page in `marketing/src/pages/admin/waitlist.astro`)
- API: `GET /api/admin/waitlist` (list + filters) and
  `POST /api/admin/waitlist/[id]/status` (status + notes)
- Auth: **basic password gate, not user accounts.** Single admin secret env var
  (`WAITLIST_ADMIN_PASSWORD`), checked against a query param or a login form that
  sets a signed cookie. Scoped to this one table — do NOT build the Shira
  marketing-admin shell for this.
- D1 access: the same `DB` binding the waitlist sink uses; read via prepared
  statements (the endpoint file already does this).
- UI: plain Astro + the existing Tailwind tokens; status buttons per row; no
  client framework needed.

## Column contract (already live, migration 0001)

`id, name, email (UNIQUE), phone, county, acreage, crop,
 status IN ('new','contacted','enrolled','declined','lost') DEFAULT 'new',
 notes, source DEFAULT 'website', created_at, updated_at`

The status enum is already the funnel state machine — the CRM surface just
drives it.

## Out of scope (until proven)

- Bulk actions (multi-select → contacted)
- CSV export (D1 console can do it for now)
- WhatsApp/Resend integration for status changes (the confirmation email
  already fires at signup; follow-up comms stay manual in the pilot)
- Lead scoring / sequences (Shira's audit item E — post-pilot)

## Definition of done

- [ ] `/admin/waitlist` lists rows + filters + status counters
- [ ] Status update persists + stamps `updated_at`
- [ ] Unauthenticated access returns 401 and logs nothing sensitive
- [ ] One deploy (manual Pages deploy from workspace copy, per the repo's
      no-git-integration setup)
- [ ] Josh smoke test: flip one test row `new → contacted`, verify in D1
