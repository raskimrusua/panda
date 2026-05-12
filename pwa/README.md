# Panda PWA

React 19 + Vite + TypeScript strict + Tailwind 4. Deployed to Cloudflare Pages
(`panda-pwa` project) at `app.panda.shira.farm`.

## Quick start

```bash
cd pwa
npm install
cp .env.example .env.local   # edit VITE_API_URL if not localhost:8000
npm run dev
```

Open http://localhost:5173.

## Scripts

| Command | What it does |
|---|---|
| `npm run dev` | Vite dev server, hot reload |
| `npm run build` | Type-check then production build to `dist/` |
| `npm run typecheck` | `tsc --noEmit` only |
| `npm run test` | Vitest one-shot |
| `npm run test:watch` | Vitest watch |
| `npm run lint` | ESLint (zero warnings allowed) |
| `npm run format` | Prettier write |

## What's in PR #8 (this scaffold)

- Vite + React 19 + TS strict + Tailwind 4 + Workbox PWA
- Auth: register / login / logout (Sanctum bearer tokens via axios interceptor)
- App shell with sidebar nav
- Pages: Dashboard, Season list, New season form, Season detail (timeline + inputs tabs)
- Crop catalogue dropdown sourced from `/api/v1/crops`
- PDF report download link to `/api/v1/seasons/{id}/report`

## Deferred to later PRs

| Surface | When |
|---|---|
| Log activity / cost / harvest forms | PR #9 |
| Disease scan + camera | PR #10 |
| Dealer map (Leaflet) | PR #10 |
| Price chart | PR #10 |
| Offline write queue + IndexedDB sync | PR #11 |
| i18next + Swahili strings + `/lint:i18n` | PR #11 |
| HelpTooltip + WelcomeModal | PR #12 |

## Deploy

Same Pattern C as Shira's frontend. From `pwa/`:

```bash
npm run build
# Source the joshkim04 CF token (same one that ships farmcore-pwa)
set -a && . ../website/.env.deploy 2>/dev/null && set +a
npx wrangler pages deploy dist --project-name=panda-pwa --branch=main --commit-dirty=true
```

The Cloudflare project `panda-pwa` is provisioned in the Hetzner provisioning
session along with `panda-marketing`.
