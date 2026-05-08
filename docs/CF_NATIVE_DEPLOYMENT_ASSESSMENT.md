# Panda — CF-Native Deployment Assessment

**Question:** Now that Panda's backend is Laravel (not Django), can it deploy entirely on Cloudflare? Would server setup be less frictioned?

**Date:** 2026-05-07
**Pricing data:** May 2026, current as of Cloudflare Containers GA (2026-04-13)

---

## TL;DR

**Yes**, Cloudflare can now run Laravel — Containers went GA on 2026-04-13 (3 weeks before this assessment). **No**, it is not less frictioned for Panda at pilot scale. The CF-native path is **10–15× more expensive than Hetzner** across all scale tiers (200 → 1000 → 10000 farmers), while delivering equivalent latency to Kenyan users (both Frankfurt-area; CF compute does not have an Africa region). The "less ops" argument is real (no SSH, no nginx config, auto-scaling) but does not justify the cost delta on a pre-revenue pilot product.

**Recommended path:** Hetzner CX22 stays the default. Revisit CF-native at year-2 scale if/when:
- Hetzner box hits resource ceiling (>10K farmers AND single-box 4 vCPU not enough)
- A non-Kenya market emerges (where multi-region becomes relevant)
- Team explicitly wants Workers Containers as a portfolio investment

---

## What changed since the original plan

CF Containers reached GA on **2026-04-13** with these key facts:

- **Pricing:** Workers Paid plan ($5/mo) includes 375 vCPU-minutes + 25 GiB-hours + 200 GB-hours disk. Beyond that: $0.000020/vCPU-sec + $0.0000025/GiB-sec + $0.00000007/GB-sec.
- **Instance sizes:** lite (1/16 vCPU, 256 MiB), basic (1/4 vCPU, 1 GiB), standard-1 (1/2, 4 GiB) up to standard-4 (4 vCPU, 12 GiB).
- **Cold-start:** Containers sleep when idle; charges stop. Cold-start re-wake is ~500ms-1s for a PHP-FPM container. Sub-50ms for Workers (V8 isolates).
- **Regions:** No Africa compute region. Workers Containers run in NA / EU / Oceania / Korea / Taiwan. CDN edge reaches Kenya via 320+ city PoPs but compute does not.
- **Hyperdrive (Postgres connection pooler):** Free, included with Workers Paid. Connects to external Postgres providers.
- **Queues:** $0.40 per million operations. Free 1M operations/month included.

The pre-GA assessment in the original plan was based on stale knowledge (Q4 2025 beta state). GA changed two things:
1. CF Containers is now an officially supported production target.
2. Pricing model is now stable enough to model honestly.

It did not change:
- The need for external Postgres (D1 = SQLite, not Postgres; Laravel doesn't ship a D1 driver).
- The need for external Redis (no native CF Redis primitive yet).
- The cost calculus at small scale (CF still pays-per-second; VPS pays-per-month).

---

## Stack comparison — three viable shapes

| Shape | Backend | DB | Queue | Redis | Marketing/PWA |
|---|---|---|---|---|---|
| **A. Hetzner all-in-one (current plan)** | Laravel on CX22 Docker | Postgres on same box | Horizon on same box | Redis on same box | CF Pages |
| **B. CF Containers + external** | Laravel on CF Containers | Neon Postgres (Frankfurt) | Horizon on CF Container OR CF Queues | Upstash Redis | CF Pages |
| **C. Workers + D1 + Queues (rewrite)** | TS/Hono on Workers | D1 (SQLite) | CF Queues | KV (limited) | CF Pages |

**Shape C is out of scope** — it throws away the Laravel skill investment we just commissioned. UWC's "first Laravel build" never happens. Not considered further.

The real question is **A vs B**.

---

## Cost modeling

### Assumptions

- **Per farmer**: ~50 API calls/day (timeline view, log activity, log cost, view dealer map, disease scan)
- **Average request**: ~100ms PHP-FPM execution time
- **Always-on**: Horizon queue worker polls Redis every ~1 second
- **Postgres**: ~1 GB at 1000 farmers, scales linearly
- **Redis**: ~50 MB at 1000 farmers (sessions + queue state + cache)
- **Egress**: ~5 KB/request (compressed JSON), occasional 1 MB image upload

### Shape A — Hetzner CX22 (Frankfurt)

| Scale | API + Horizon + Postgres + Redis | Egress | Total /mo |
|---|---|---|---|
| 200 farmers | $4 (CX22 — already paid for Shira; **$0 marginal**) | included in 20 TB | **$0–4** |
| 1,000 farmers | $4 (CX22 still fits) | included | **$4** |
| 10,000 farmers | $8 (CX32 upgrade — 4 vCPU, 8 GB, $8/mo) | included | **$8** |

CX22 specs: 2 vCPU, 4 GB RAM, 40 GB SSD, 20 TB traffic. Hetzner Frankfurt → Kenya RTT ~130ms.

### Shape B — CF Containers + Neon + Upstash

#### At 200 farmers

| Component | Calculation | Cost |
|---|---|---|
| CF Workers Paid (base) | $5/mo includes 375 vCPU-min + 25 GiB-hr | **$5** |
| API container | 200×50×30 = 300K req/mo × 0.025 vCPU-sec/req = 7,500 vCPU-sec = 125 vCPU-min. **Within 375 included.** | $0 |
| Horizon (always-on basic) | 1/4 vCPU × 86,400 × 30 = 648K vCPU-sec = 10,800 vCPU-min. **10,425 over included.** × 60 × $0.000020 | $12.51 |
| Horizon memory | 1 GiB × 86,400 × 30 = 2.59M GiB-sec. **2.50M over 90K included.** × $0.0000025 | $6.26 |
| Neon Postgres (Launch tier) | Always-on 0.25 vCPU × 720h × $0.106/CU-hr | $19.08 |
| Neon storage | ~1 GB × $0.35 | $0.35 |
| Upstash Redis (Pay-as-you-go) | Horizon polls 2.6M cmd/mo + queue + sessions ≈ 10M cmd × $0.20/100K | $20.00 |
| Egress | ~7.5 GB/mo (within 1 TB included) | $0 |
| **TOTAL** | | **~$63/mo** |

#### At 1,000 farmers

| Component | Cost |
|---|---|
| CF Workers Paid base | $5 |
| API container (1.5M req → 625 vCPU-min) | $0.30 |
| Horizon always-on | $18.77 |
| Neon (slightly more compute, ~0.4 CU avg) | $30 |
| Neon storage (~5 GB) | $1.75 |
| Upstash Redis (~25M commands) | $50 |
| Egress (~37 GB) | $0 (within 1 TB) |
| **TOTAL** | **~$106/mo** |

#### At 10,000 farmers

| Component | Cost |
|---|---|
| CF Workers Paid base | $5 |
| API container (15M req → 6,250 vCPU-min, 5,875 over) × 60 × $0.000020 | $7.05 |
| API memory overage | $0.71 |
| Horizon (now standard-1 = 1/2 vCPU, 4 GiB always-on) | $46 + $26 | $72 |
| Neon (~1 vCPU sustained) | $76 |
| Neon storage (~50 GB) | $17.50 |
| Upstash Redis (~250M commands; jump to Fixed plan or large pay-as-you-go) | $150 |
| Egress (~375 GB) | $0 (within 1 TB) |
| **TOTAL** | **~$328/mo** |

### Side-by-side

| Scale | Hetzner | CF-Native | Multiple |
|---|---|---|---|
| 200 farmers | **$0–4** | $63 | **15–63×** |
| 1,000 farmers | **$4** | $106 | **27×** |
| 10,000 farmers | **$8** | $328 | **41×** |

---

## Latency to Kenya

| Path | RTT to Nairobi |
|---|---|
| Hetzner Frankfurt → Nairobi | ~130 ms |
| CF Workers Containers (EU region) → Nairobi | ~130 ms (same as Hetzner — both routed via Europe) |
| CF Edge (Nairobi PoP) → Nairobi user | ~5 ms (but only for cached/edge content, not API calls) |
| Hyperdrive cache hit (Nairobi PoP) → Nairobi user | ~5 ms (read-only, cached queries only) |
| Neon Frankfurt → Hetzner Frankfurt | ~5 ms (in-region) |
| Neon Frankfurt → CF EU compute | ~5 ms (in-region) |

**Conclusion:** Kenya latency is roughly equivalent for both shapes. The CF edge (Nairobi PoP) helps for *static assets* and *Hyperdrive-cached reads* but does not help PHP-FPM API requests, which need to hit compute in Europe. Both Hetzner and CF compute are ~130 ms RTT to a Nairobi farmer.

---

## Operational friction — the qualitative side

### Where Hetzner adds friction

- SSH access required for ops
- Docker-compose YAML maintenance
- nginx vhost config per service
- Manual SSL certificate management (Cloudflare Origin Cert + nginx reload)
- Backup script ownership
- Single-region (no automatic geographic redundancy)
- Manual scaling (resize VPS, possibly with downtime)

### Where CF-Native adds friction

- New service learning curve (Workers Containers is 3 weeks GA at this writing)
- Multi-vendor coordination (CF + Neon + Upstash + Resend = 4 dashboards, 4 billing relationships)
- Per-second cost surveillance (always-on Horizon worker is the dominant cost; need to re-architect to use CF Queues to optimize, but that's a Laravel→TS rewrite for the worker side)
- Less mature Laravel-on-Containers community / fewer canonical examples
- Cold-start latency on idle endpoints (~500ms–1s the first request after sleep)
- Vendor lock-in (porting away requires re-engineering)

### The "less ops" claim, scrutinised

CF-native does eliminate SSH, docker-compose, nginx, and SSL. **It does not eliminate Postgres ops** (you still configure Neon, manage credentials, monitor connections). **It does not eliminate Redis ops** (Upstash dashboard, command rate monitoring). **It does not eliminate queue ops** (whether Horizon or CF Queues, you still observe + alert on queue depth + failures).

Net: ~50% reduction in SSH-flavoured ops, replaced by ~3 new vendor dashboards. Real but not as dramatic as marketing suggests.

---

## What it would take to make CF-native worth it

The CF-native math becomes defensible if any of these become true:

1. **Cost crosses over** — at >100,000 farmers Hetzner CX42 (~$30/mo) vs CF (~$1,000+/mo). Hetzner still wins.
2. **Multi-region is real** — Panda expands to Tanzania + Uganda + Rwanda + Nigeria, where geographic distribution actually matters. Then CF auto-routes to nearest compute region.
3. **CF lands a Cape Town / Johannesburg compute region** — would cut Kenya RTT to ~50ms (currently ~130ms via EU). Not announced as of May 2026.
4. **Laravel Cloud lands a Frankfurt or Africa region with serverless pricing** — currently US/EU/MEA only, no auto-scale-to-zero, fixed-cost. Not yet competitive vs Hetzner.
5. **CF Queues + Workers replaces Horizon** — reduce always-on container cost. Requires rewriting queued jobs in TypeScript (Workers don't run PHP). Throws out the Laravel ergonomics for queues.

None of these are true today. Revisit annually.

---

## Hybrid option worth knowing about

There is a middle ground not in the original plan: **Laravel API on Hetzner + CF Hyperdrive in front of Hetzner Postgres**. Hyperdrive caches Postgres queries at CF's edge for free.

- Hetzner: backend, Horizon, Redis (free with VPS)
- CF Hyperdrive: Postgres connection pooler + edge cache (free with $5/mo Workers Paid plan)
- Laravel app uses standard `pgsql` driver pointed at Hyperdrive's connection string
- Reads cached at Nairobi PoP for ~5ms RTT vs ~130ms direct

**Cost:** $5/mo (Workers Paid) on top of Hetzner's $4/mo = $9/mo total at 200 farmers.

**Benefit:** Some farmer-facing reads (catalog of 17 crops, dealer directory) become near-instant in Nairobi.

**Complexity:** Adds one more service to the stack. Hyperdrive only helps reads, not writes. May not be worth $5/mo for a pilot.

**Recommendation:** Defer Hyperdrive to year-2 unless P5 user testing shows latency complaints. Cheaper to just run Hetzner direct + CF in front of marketing/PWA.

---

## Final recommendation

**Stay with Hetzner CX22 for Panda P1–P8.** Original plan stands.

Specifically:
- **Don't move Panda to CF Containers.** 15–63× cost increase for equivalent latency.
- **Don't add Hyperdrive yet.** $5/mo Workers Paid plan unjustified at pilot scale; revisit if P5 user testing shows >2s API response complaints.
- **Don't switch to Laravel Cloud.** No Africa region, no significant latency improvement, vendor lock-in.
- **Don't rewrite to Workers + D1 + Queues.** Wastes the Laravel investment. Different conversation if a different greenfield product comes up.

**Revisit this assessment annually** or when:
- CF announces an Africa compute region
- Hetzner CX22 hits resource ceiling
- Panda expands beyond Kenya (multi-region becomes real)
- Laravel Cloud adds Frankfurt + auto-scale-to-zero pricing

---

## Sources (May 2026)

- [Cloudflare Containers Pricing — developers.cloudflare.com](https://developers.cloudflare.com/containers/pricing/)
- [Containers and Sandboxes are now generally available — 2026-04-13 changelog](https://developers.cloudflare.com/changelog/post/2026-04-13-containers-sandbox-ga/)
- [Cloudflare Hyperdrive Pricing](https://developers.cloudflare.com/hyperdrive/platform/pricing/)
- [Cloudflare Containers — regional placement changelog 2026-04-05](https://developers.cloudflare.com/changelog/post/2026-04-05-regional-placement/)
- [Hetzner CX22 specs — sparecores.com](https://sparecores.com/server/hcloud/cx22)
- [Hetzner price adjustment — docs.hetzner.com](https://docs.hetzner.com/general/infrastructure-and-availability/price-adjustment/)
- [Neon Postgres Pricing](https://neon.com/pricing)
- [Upstash Redis Pricing](https://upstash.com/pricing/redis)
- [Laravel Cloud Pricing + Regions](https://cloud.laravel.com/pricing)
- [Cloudflare Queues Pricing](https://developers.cloudflare.com/queues/platform/pricing/)
