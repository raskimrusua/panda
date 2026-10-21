# Panda Media Generation Process — Replicated from Shira + MyChama

Prepared 2026-10-20 (panda-launch-track). Source patterns: Shira (`~/Desktop/farmcore/social_media/` + `docs/branding/`) and MyChama (`~/Desktop/mychama-social-assets/`).

## The two patterns we're copying

### Pattern A — Shira: deterministic PIL generator (no AI cost)

- **Brand assets:** AI logo generators (Recraft.ai with SVG export, Ideogram) driven by structured prompts in `docs/branding/AI_PROMPTS.md`; the chosen mark is then polished programmatically (`generate_logos.py`, PIL).
- **Hero illustrations:** 8 flat brand illustrations in `docs/branding/final/hero-illustrations/` (hero-1..8.png) — AI-generated from the same prompt pack, then used as composable art.
- **Social campaign:** `social_media/generate.py` + `month_data.py` — a fully programmatic PIL generator producing stat cards, quote cards, memes, infographics, carousels, before/after and social-proof cards at 1080×1080, all 12 months, from a 79KB content dataset. Deterministic, versionable, zero per-image cost.
- **OG image:** `generate_og_image.py` — programmatic 1200×630 from brand canvas + hero illustration + wordmark.
- **Distribution:** `upload-campaign-to-r2.sh` → R2, `build_social_schedule.py` → D1 social tables, published on schedule by the `shira-cron` worker.

### Pattern B — MyChama: Claude Design prompt pack (human-in-the-loop, design quality)

- **Onboarding pack** (`claude-design-onboarding.md`): brand voice, design tokens, platform bios, product facts — pasted into Claude Design once to build the design system.
- **Prompt batches** (`claude-design-prompts.md`): every card follows *Format → Audience → Purpose → Specific content → Style anchor → 3 variations*, with ready caption companions per channel (IG/FB/LinkedIn).
- **Caveats:** Claude Design is a claude.ai canvas product — Claude Code cannot invoke it; it's a manual paste-and-collect loop. Output lands in `claude-design-output/`.

## Panda adaptation (this repo)

Panda has **no brand assets at all today** — no favicon, no og-image, no logo files, no `public/` directory (all verified 404/absent on 2026-10-20). We start from the wordmark ("Panda." in highland-green with a panda-leaf dot) and the inherited Shira palette plus two Panda accents (`panda-leaf #74C69D`, `panda-dawn #FFF1E0`).

**Chosen mix:**

1. **Brand mark** — Pattern A (programmatic): a simple geometric leaf-and-dot mark built in PIL, matching the wordmark. AI logo generators (Recraft) can upgrade it later; the programmatic mark unblocks everything today. Ship as `public/logo-horizontal.png`, `public/favicon.svg`.
2. **OG image (site-wide)** — Pattern A: PIL canvas, palette + leaf-field motif + wordmark. Fixes the 404.
3. **Per-post hero images** — Pattern A hybrid: one flat, consistent "field card" template (1600×900) with a simplified crop glyph drawn per crop (kale rosette, tomato cluster, bean pods, mango, onion bulb). Deterministic, honest (no fake photos, no fake testimonials), on-brand. Real shamba photography can replace these later — Pattern B (Claude Design) is the quality-upgrade path once real photos exist.
4. **Social card generator** — Pattern A port (later): `generate.py`-style card types fed from the 17 crop JSONs. Deferred until the social channels launch (spec: `docs/social-channels.md`).
5. **Distribution** — adapted: **no R2 on the joshkim04 account** (account-level disabled, binding removed). Images ship in the repo (`marketing/public/`) and deploy with the site. No cron: posting is manual at 2–3/week for the pilot.

## Files (all under `social_media/` in this repo)

- `assets/fonts/DMSans-Regular.ttf`, `DMSans-Italic.ttf` — copied from Shira's toolkit (same brand font family).
- `generate_media.py` — one CLI: `--og`, `--headers`, `--favicon`, `--logo`, `--all`. Outputs into `marketing/public/`.
- `README.md` — how to regenerate everything.

## Content rules (carry over from both patterns + Rule 47)

- No fabricated photos of "real farms"; illustrations are clearly stylized.
- No fake stats, testimonials, or certification claims on any card.
- Agronomy claims on cards must match Silas-signed crop content (crop-signoff-checklist).
- Palette is locked in `generate_media.py` constants (single source of truth, Rule 24).

## Runbook

```bash
cd social_media
python3 generate_media.py --all        # writes marketing/public/{og-image.png, favicon.svg, logo-horizontal.png, blog/*.png}
cd ../marketing
npm run build && wrangler pages deploy dist --project-name panda-marketing --branch main --commit-dirty=true
```
