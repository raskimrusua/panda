# Panda Social Channels — Launch Spec (Kenyan Horticulture Audience)

Prepared 2026-10-20 (panda-launch-track). Status: **spec — launch only with Josh's approval.**

## Platform choice

| Platform | Launch? | Why / role |
|---|---|---|
| **WhatsApp (channel + groups)** | ✅ Primary | Where Kenyan farmers already are. A WhatsApp Channel broadcasts are the lowest-friction subscribe for a smallholder audience. Group links live in the recruitment pack. |
| **Facebook Page** | ✅ Yes | Largest reach for 30+ rural Kenyan farmers; shareable crop guides; FB is the default "internet" for many. |
| **YouTube** | ✅ Yes (lite) | Short 2–4 min crop guides in Swahili/English — highest trust format for agronomy. Start with 1 video per signed-off crop, filmed on a phone. |
| **TikTok** | ⚠️ Later | Great for 18–30 urban/grocer audience, but the pilot cohort skews older rural. Revisit after pilot if youth engagement shows. |
| **X / Twitter** | ❌ No | Agronomist/extension officers read it, but not the farmers; costs more than it returns pre-pilot. |
| **Instagram** | ⚠️ Later | Brand surface for partners/donors; not a farmer acquisition channel in Meru/Kirinyaga. |

## Handle + branding

- Name: **Panda Kenya** everywhere; handle preference `@pandakenya` (verify availability on FB/YT before claiming).
- Bio copy (FB): "Farm planning for Kenyan smallholders. 17 crops, season plans, disease scanner, market prices. Inspired by SHEP PLUS. Pilot: Meru & Kirinyaga."
- Link: `https://panda.shira.farm/?utm_source=facebook&utm_medium=social&utm_campaign=pilot-2026#waitlist` (per-platform `utm_source`).

## Content model — repurpose the blog, not new content

Every blog post (5 seeded, more to come from the 17-crop catalogue) produces:

1. **FB post** — 2–3 sentence takeaway + the strongest number + link. Example: "Kale keeps giving: 20 picks per season if you top-dress at week 4. Full plan →" + link.
2. **WhatsApp Channel broadcast** — shorter, Swahili-first version of the same takeaway.
3. **YouTube short** (when video exists) — the post's core topic in 2–4 min, farm voiceover optional.

Cadence: **2–3 posts/week** until pilot fill; then 1/week + pilot stories (farmer wins, launch milestones).

## Rules

- Same compliance as recruitment copy: "Inspired by JICA SHEP PLUS" only; no JICA partnership claim; no yield guarantees; disease advice is advisory (KEPHIS/PCPB disclaimer pattern).
- Never publish a crop's social post before Silas signs off that crop (`docs/crop-signoff-checklist.md`) — the post claims are agronomic claims.
- All social links use the UTM'd waitlist links from `docs/pilot-recruitment-pack.md`.
- Comment moderation: FB comments asking agronomy questions get the standard answer + a link to the waitlist; never diagnose a specific photo publicly — point to the pilot's in-app scan.

## Deferred (Rule 23 — extract at 3)

- Shira's 5-platform scheduler (shira-cron social publisher) is NOT ported yet — manual posting is fine at 2–3/week for the pilot.
- No paid boost until organic content shows which posts farmers actually engage with (3+ posts with data).
- Metrics to track from day one: signups per `utm_source` in `panda_waitlist.source` (already wired by the source-capture patch).
