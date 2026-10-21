# Silas Sign-Off Checklist — 17-Crop SHEP PLUS Catalogue

Owner: Josh (process) / Silas (review). Last updated: 2026-10-20.

## How sign-off works

1. Each crop's agronomic content lives in `api/resources/content/crops/<slug>.json`
   (ecological requirements, varieties, timelines, inputs, sprays).
2. `crops.has_full_content` is a **DB flag** (seeded false on catalogue
   creation, PR #35). It gates farmer-facing planting: unreviewed crops show
   in the catalogue with a "Coming soon" badge and cannot be planted from the PWA.
3. Silas reads the JSON (Filament `/admin/crops` renders it), confirms the
   timeline + inputs + sprays are accurate for Kenyan field conditions.
4. Josh (superuser) flips `has_full_content = true` in Filament **only after**
   Silas's confirmation. Rule L2 / anti-pattern: never self-sign agronomy you
   authored — wrong advice = crop loss = liability.
5. This checklist is the paper trail: tick a row only when the flip has been
   made in the production DB (`/admin/crops`).

## Checklist

Legend: JSON = authored content file present. Reviewed = Silas read it.
Signed = `has_full_content=true` in prod DB.

| # | Crop | Slug | JSON | Silas reviewed | Signed (DB) | Date | Notes |
|---|---|---|---|---|---|---|---|
| 1 | Tomato | `tomato` | ✅ | ☐ | ☐ | | Reference crop (P5 demo). Verify its spray schedule + TYLCV guidance first |
| 2 | Kale (Sukuma wiki) | `kale` | ✅ | ☐ | ☐ | | |
| 3 | Cabbage | `cabbage` | ✅ | ☐ | ☐ | | |
| 4 | Bulb Onion | `bulb-onion` | ✅ | ☐ | ☐ | | |
| 5 | French Beans | `french-beans` | ✅ | ☐ | ☐ | | |
| 6 | Capsicum | `capsicum` | ✅ | ☐ | ☐ | | |
| 7 | Chili | `chili` | ✅ | ☐ | ☐ | | |
| 8 | Eggplant | `eggplant` | ✅ | ☐ | ☐ | | |
| 9 | Potato | `potato` | ✅ | ☐ | ☐ | | |
| 10 | Watermelon | `watermelon` | ✅ | ☐ | ☐ | | |
| 11 | Amaranthus | `amaranthus` | ✅ | ☐ | ☐ | | |
| 12 | Black Nightshade | `black-nightshade` | ✅ | ☐ | ☐ | | |
| 13 | Cowpea Leaves | `cowpea-leaves` | ✅ | ☐ | ☐ | | |
| 14 | Mango | `mango` | ✅ | ☐ | ☐ | | |
| 15 | Avocado | `avocado` | ✅ | ☐ | ☐ | | |
| 16 | Banana | `banana` | ✅ | ☐ | ☐ | | |
| 17 | Passion Fruit | `passion-fruit` | ✅ | ☐ | ☐ | | |

Progress: 0 / 17 signed.

## Batch review order (Silas's time is the constraint)

Priority order — pilot counties (Meru + Kirinyaga) grow these hardest:

1. **Tomato** — highest-value crop in both counties; also the disease-scan demo crop.
2. **Kale** — staple leafy green, fastest seed-post material.
3. **French Beans** — Kirinyaga export crop.
4. **Bulb Onion** — Meru staple.
5. **Mango** — Meru orchard crop, multi-season.
6. Cabbage, Capsicum, Chili, Eggplant, Potato, Watermelon
7. Amaranthus, Black Nightshade, Cowpea Leaves, Avocado, Banana, Passion Fruit

## Review criteria per crop (what Silas checks)

- Ecological requirements match Kenyan field reality (altitude/rainfall/temperature)
- Varieties list: commonly available from Kenyan seed suppliers
- Days-to-maturity realistic for the pilot regions
- Inputs + spray schedules: legal in Kenya, PCPB-registered products, safe application notes
- Any Kenya-specific pest/disease omitted that's common in Meru/Kirinyaga?

## Tracked in pilot pack

- Recruitment copy claims "content reviewed by a credentialed agronomist" —
  only honest after this sheet moves. Keep section 4 of
  `docs/pilot-recruitment-pack.md` in sync with actual signed count.
