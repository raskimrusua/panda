# Panda Scanner — Crop × Disease Matrix (Phase A knowledge base)

Status: DRAFT · started 2026-11-02 · feeds scanner Phase A (more crops × more diseases ×
severity classes × treatment guidance × confidence) and the crop-guide content library.

## Purpose

The disease scanner's thoroughness is bounded by this matrix. Each row is a
(crop, disease) pair the scanner must recognise and speak about. Columns:

- **Severity classes** — how the disease presents, ordered mild → severe (drives the
  severity class shown to the farmer).
- **Symptoms** — the visual features the model should key on (and the farmer sees).
- **Treatment (advisory)** — PCPB-registered product *classes*, never brand names without
  label confirmation. All guidance is advisory; extension-officer escalation language is
  mandatory in-app.
- **Prevention** — the cultural controls that appear in guides and in-app tips.
- **Scanner notes** — photo-quality cues and confusable lookalikes for the model + UI.

## Crop coverage (20-crop library)

Live guides (5): kale/sukuma, tomato, French beans, mango, onion.
Staged guides (2, this PR): maize, beans.
Queue (13): Irish potato, sweet potato, cabbage, spinach, capsicum, carrot, butternut,
coriander, avocado, banana, pawpaw, watermelon, snow peas.

---

## Maize (Zea mays)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Fall armyworm | Low (window-pane whorl feeding) · Medium (shot-holes, frass) · High (ragged whorl, dead heart) | Fresh frass in whorl, transparent leaf "windows", larvae with 4 dark spots on 8th segment | PCPB-registered larvicide classes at label rates; hand-crush in small plots | Scout whorls week 4–8; early planting; push-pull with Desmodium | Photograph the whorl open, not the whole plant; frass presence separates FAW from stalk borer |
| Maize stalk borer | Low (pin-holes) · High (tunnels, stem breakage) | Entry holes with sawdust frass, wilting "dead heart" in young plants | Registered systemic granule at planting; sprays only before tunnelling starts | Cut-and-destroy stalks after harvest; rotate | Holes near stem base vs FAW in whorl — two photos (whorl + stem) beat one |
| Grey leaf spot | Low (scattered lesions) · Medium (lesions on lower third) · High (lesions to ear leaf) | Rectangular grey-brown lesions, veins limit spread, lower leaves first | Registered triazole/strobilurin classes at early onset | Residue management, rotation, tolerant hybrids | Rectangular-not-round is the key discriminator vs rust |
| Maize lethal necrosis (MLN) | Early (mottle) · Advanced (necrosis, death) | Chlorotic mottling, yellowing from leaf tip, stunted cobs, early death | None — rogue + destroy; MLN-tolerant hybrids where available | Certified seed, control vectors, report to KEPHIS | No-cure message must be explicit in UI; escalate to extension |
| Maize streak virus | Mild (sparse streaks) · Severe (dense streaks, stunting) | Pale yellow streaks along veins, stunted growth, small cobs | None — vector (leafhopper) control early only | Tolerant varieties, early uniform planting | Streaks follow veins — key discriminator vs nutrient deficiency (interveinal) |

## Beans (Phaseolus vulgaris)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Bean fly | Seedling (tunnelled stems) · Established (swollen cracked stems, yellowing) | Seedling death, maggot tunnels at stem base, yellowing + stunting | Registered seed-dressing insecticide; early foliar only in seedling stage | Plant early + dense; avoid late planting in wet spells | Photograph the stem base — leaf photos miss the tunnel |
| Angular leaf spot | Low (scattered) · High (defoliation) | Brown angular lesions with grey centres, on leaves + pods, worst in wet weather | Registered contact/systemic fungicide classes at first lesions | Rotation, clean seed, open rows | Angular (vein-bounded) shape discriminates vs anthracnose |
| Anthracnose | Seed (discoloured seed) · Field (lesions + pod rot) | Sunken dark lesions on pods, stems, cotyledons; black seed blotches | Registered fungicide at first sign; never save seed from infected crop | Certified seed only; field hygiene | Seed-borne — a "where did the seed come from" question in-app improves the advice |
| Bean rust | Low (scattered pustules) · High (leaf death) | Orange-brown pustules on undersides, yellow halo, defoliation in humid spells | Registered rust-active fungicide class at onset | Resistant varieties (Nyota), avoid overhead watering | Pustules rub off on fingers — the field test that confirms rust |
| Root rots | Patchy (stunted patches) · Severe (stand collapse) | Dark rotting roots, stunted yellow patches, plants pull out easily | None effective — focus on drainage | Raised beds, rotation, avoid waterlogged fields | Photograph an uprooted plant with roots washed |

## Kale / Sukuma wiki (Brassica oleracea var. acephala)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Black rot | Low (V-lesions) · High (systemic, leaf loss) | Yellow V-shaped lesions at leaf margins, blackened veins, stunting | None curative — remove plants; copper classes suppress spread | Clean seed, 2–3 yr rotation, no overhead irrigation | V-shaped margin lesions are the signature; photo with leaf flat |
| Downy mildew | Low (specks) · High (grey fuzz, leaf loss) | Yellow specks above, grey-purple fuzz below, in cool damp spells | Registered downy-active fungicide at first specks | Spacing/airflow, resistant varieties (Mfalme), morning watering | Photograph the UNDERSIDE — fuzz is invisible from above |
| Alternaria leaf spot | Low · High (holes, defoliation) | Dark brown spots with rings, "shot-holes" as centres fall out | Registered alternaria-active fungicide class | Rotation, remove debris, avoid leaf wetness at night | Ringed spots (target pattern) discriminates vs downy mildew |

## Tomato (Solanum lycopersicum)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Late blight | Low (specks) · High (stem lesions, fruit rot, collapse in days) | Water-soaked spots → brown lesions, white fuzz at margins in humidity, green stem lesions, greasy fruit rot | Registered late-blight fungicide classes, strict schedule in wet weather | Resistant varieties, spacing, staking, morning irrigation, destroy volunteers | The fast killer — ask "how many days from first spot to spread" in-app; escalate fast |
| Early blight | Low (leaf spots) · High (defoliation, fruit shoulder rot) | Target-ring brown spots on older leaves, yellowing, fruit shoulder lesions | Registered alternaria-active classes at first rings | Rotation (avoid potato/pepper), mulching, balanced nitrogen | Ring pattern on OLDER leaves first — vs late blight starting on new growth |
| Bacterial wilt | Early (day-wilt) · Advanced (permanent wilt, ooze) | Sudden wilt of one side, plants stay green, white ooze from cut stem in water | None — rogue + destroy; grafted rootstocks where economic | Rotation, raised beds, avoid flooding | Cut-stem-in-water test is the in-app verification step |
| Tomato yellow leaf curl (TYLCV) | Mild (curling) · Severe (stunting, no fruit) | Upward curled yellow leaves, stunted growth, flower drop | None — remove infected plants, whitefly control only | Whitefly nets/barriers, tolerant varieties, early whitefly control | Curling UP + yellow margins = virus, not water stress |

---

## Process

1. **Add crops** top-down from the 20-crop queue; **add diseases** from field reports, pilot
   scanner confusions, and extension-officer feedback (the correction loop, Phase B).
2. Every treatment cell is a *class* (e.g., "registered triazole class"), never a brand —
   brands require PCPB label verification per deployment region.
3. The advisory + escalation language is mandatory in the app UI: treatment guidance is
   advisory; the extension officer sees the same photo.
4. Scanner notes feed the model's confusable-pairs training set and the photo-quality
   guidance UI (Phase A: lighting/angle prompts per disease).
