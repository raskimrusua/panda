# Panda Blog Content Calendar — October 2026 to March 2027

**Schedule:** weekly (Tuesdays) during the pilot; moving to 2/week (Tue + Fri) once recruitment fills.
**Auto-publish:** date-driven — the daily rebuild cron (`.github/workflows/panda-daily-rebuild.yml`,
21:10 UTC = 00:10 EAT) redeploys from main; prerendered pages publish posts whose
`publishedDate` is today-or-earlier (EAT).

**Pillars (rotate to avoid repetition):**
- **CG** = Crop Guides (agronomy from the 17-crop catalogue, Silas-gated)
- **FT** = Farm Tech (irrigation, sensors, AI scanner, connectivity — Shira FT pillar adapted)
- **PL** = Product & Pilot (launch milestones, pricing, pilot stories, waitlist)
- **FM** = Farm Management (costs, yields, record-keeping, market timing)

**Synced with:** the social channels spec (`docs/social-channels.md`), the pilot recruitment
pack (`docs/pilot-recruitment-pack.md`), and the crop sign-off gate
(`docs/crop-signoff-checklist.md` — never publish a CG post for an unsigned crop).

---

## Shipped / staged (October–December 2026)

| Date (Tue) | Title | Pillar | Status |
|---|---|---|---|
| 2026-10-20 | Sukuma Wiki: How to Get More From Kenya's Most-Plantable Kale Crop | CG | LIVE |
| 2026-10-27 | Tomato Economics: Tylka F1 vs Cal-J and the Spray Schedule That Protects Both | CG | STAGED |
| 2026-11-03 | French Beans for Export: The 50-Day Crop That Pays in Cold-Chain Discipline | CG | STAGED |
| 2026-11-10 | Mango in Meru: Apple vs Tommy Atkins, and the 13 °C Rule That Protects Your Harvest | CG | STAGED |
| 2026-11-17 | Bulb Onion: Red Creole vs Texas Grano and the Curing Step That Doubles Your Storage Window | CG | STAGED |
| 2026-11-24 | Drip Irrigation for One Acre: What It Costs in 2026 and Where the Money Actually Goes | FT | STAGED |
| 2026-12-01 | Your Phone Can Now Read a Sick Leaf: How the Panda Disease Scanner Works | FT | STAGED |
| 2026-12-08 | Soil Moisture Sensors for Smallholders: What a KES 1,500 Node Tells You That Guesswork Can't | FT | STAGED |
| 2026-12-15 | Built for the Phone in Your Pocket: Why Panda Chose Offline-First for Kenyan Farms | FT | STAGED |
| 2026-12-22 | Sell When the Market Pays: How Price Data Changes Your Harvest Week | FT | STAGED |

## Next queue (January–March 2027)

- **CG backlog (12 crops):** cabbage, capsicum, chili, eggplant, potato, watermelon,
  amaranthus, black nightshade, cowpea leaves, avocado, banana, passion fruit —
  one per week as Silas signs them off (see crop-signoff-checklist; 0/17 signed).
- **FT arc (from Shira's 30-post series, adapted):** where to buy sensors in Kenya ·
  water tank + borehole monitoring · greenhouse/polytunnel automation · drones/NDVI
  (when to hire) · SACCO/chama group buying · farm-tech failure modes · the KES
  phased budget for a smallholder · insurance + records.
- **PL beats:** pilot launch announcement · first 100 signups · first harvest recorded
  in Panda · 40% WTP result · pricing decision · farmer story #1 (Meru) · farmer
  story #2 (Kirinyaga).
- **FM beats:** cost-per-kilo by crop · record-keeping for loans · harvest grading
  guides · the glut calendar (which months flood, per crop).

## Content rules

- All CG posts cite the crop JSON data; publish only after Silas sign-off for that crop.
- Price claims: researched or labelled indicative, with a source footnote (see the FT posts).
- No fabricated stats, testimonials, or photos; disease advice carries the KEPHIS/PCPB
  advisory pattern.
- Every post ends with the UTM'd pilot waitlist CTA (`utm_source=blog`).
