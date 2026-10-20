# Panda — Project Documentation (Public)

**Public-facing technical reference. No secrets, no internal infrastructure details.**

> Structure mirrors the internal doc with sensitive details stripped per BUSINESS.md §5.
> This document can be shared with agronomists, partners, and technical evaluators.

---

## 1. Project Overview

**Panda** is a Progressive Web App that helps Kenyan smallholder horticulture farmers manage their crops, track costs and harvests, detect crop diseases using AI, find input dealers, and monitor market prices.

**Origin:** Inspired by JICA SHEP PLUS methodology. Built as a standalone product (not a module of any other platform).

**Status:** Pre-Launch (MVP built, pilot waitlist open at panda-marketing.pages.dev)

**17 crops supported:** Tomato, Kale, Cabbage, Bulb Onion, French Beans, Capsicum, Chili, Eggplant, Potato, Watermelon, Amaranthus, Black Nightshade, Cowpea Leaves, Pumpkin Leaves, Spider Plant, Carrot, Coriander

**Languages:** English + Swahili (full i18n)

---

## 2. Technology

| Layer | Technology |
|---|---|
| Backend API | Laravel 11 (PHP 8.3) |
| Database | PostgreSQL 16 |
| Cache & Queues | Redis 7 + Laravel Horizon |
| Frontend (PWA) | React 19 + TypeScript + Tailwind CSS 4 |
| Marketing Site | Astro 5 |
| Hosting | Hetzner (Frankfurt) + Cloudflare Pages |
| Disease AI | Crop.health Kindwise API (planned; mock in development) |

---

## 3. Key Features

- **Season management** — create crop seasons, log activities, track costs
- **Cost & profit tracking** — cumulative cost-vs-revenue charts, profit summaries
- **Disease detection** — photo-based AI diagnosis with advisory disclaimers
- **Market prices** — daily prices from 30+ dealers, 1,560+ price records in demo
- **Dealer directory** — GPS-enabled input supplier finder
- **Offline-first PWA** — works without internet, syncs when connected
- **Bilingual (EN/SW)** — every screen available in Swahili
- **Farmer data ownership** — all data per-farm isolated; farmers control their records

---

## 4. Data Privacy

Panda complies with Kenya's Data Protection Act 2019:

- **Explicit consent at registration** — farmers must actively accept Terms of Service and Privacy Policy
- **Auditable consent log** — every acceptance is recorded immutably
- **Version-aware reconsent** — if policies update, farmers are prompted to re-accept
- **Advisory disclaimers** — disease detection and market price data carry clear usage notices
- **Image consent** — disease photo uploads require farmer consent flag
- **Data retention** — default 24 months for disease photos (configurable per farm)

**Operating entity:** Panda is operated by [entity TBD — separate from Shira/MyChama].  
**Data Protection Officer:** [TBD — to be published in Privacy Policy].  
**ODPC Registration:** Pending.

---

## 5. API Overview

**Base URL:** `https://api.panda.farm/api/v1/` (planned)

All endpoints require authentication (Bearer token). Responses use JSON:API-style envelopes.

**Core resources:** Seasons, Crops, Activities, Costs, Harvests, Disease Detections, Dealers, Market Prices

Full API documentation will be published at launch.

---

## 6. Accessibility

- WCAG 2.2 AA target
- Full keyboard navigation
- Screen reader support
- Color contrast compliance (Tailwind 4 defaults)
- Swahili translations for all user-facing text

---

## 7. Offline Support

The PWA caches essential data and supports offline activity logging. When connectivity returns, queued data syncs to the server automatically.

---

## 8–20. Sections to be populated at launch

Remaining sections (pricing, deployment architecture, third-party integrations, compliance details, roadmap) will be filled in when Panda goes live.

---

*Document created: 2026-05-16 — P5/P6 scaffold.*
