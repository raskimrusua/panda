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

Live guides (5): kale/sukuma, tomato, French beans, mango, onion (all now covered in the matrix).
Staged guides (15): maize, beans, Irish potato, sweet potato, cabbage, spinach, capsicum, carrot, butternut, coriander, avocado, banana, pawpaw, watermelon, snow peas.
Queue (0): **20-crop library COMPLETE** — all crops covered in guides + matrix.

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

## Irish potato (Solanum tuberosum)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Late blight | Low (first spots) · High (stem + tuber rot, field collapse in ~10 days) | Water-soaked spots browning fast, white fuzz at lesion margins in humidity, blackened stems, tuber red-brown rot | Registered late-blight fungicide classes on a strict preventive schedule in wet spells | Certified seed, resistant varieties where available, wide spacing, no volunteer plants | The fastest killer in the catalogue — in-app escalation when lesions spread within days |
| Early blight | Low (leaf spots) · High (defoliation) | Target-ring brown spots on OLDER leaves first, yellowing, weak stand | Registered alternaria-active classes at first rings | Rotation away from tomato/pepper, balanced nitrogen, mulching | Ring pattern starting low on the plant discriminates vs late blight |
| Bacterial wilt | Early (day-wilt) · Advanced (permanent wilt, tuber ooze) | Green wilt of one side, recovery at night then permanent; grey ooze from tuber eyes when squeezed | None — rogue + destroy; 3+ season rotation away from solanaceous crops | Certified seed only, clean tools, avoid flooding | Tuber-eye ooze test is the in-app verification; photo leaves + cut tuber |
| Potato viruses (PLRV/PVY) | Mild (curl) · Severe (stunting, small tubers) | Leaf curl, mottling, stunted plants, aphid colonies present | None — vector (aphid) control early only | Certified seed, rogue early infections, aphid monitoring | Ask about seed source in-app — saved seed + aphids = virus pattern |

## Sweet potato (Ipomoea batatas)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Sweet potato weevil | Low (scattered tunnels) · High (bitter, rotting roots) | Larvae tunnel roots + vines, root surface pimpled with exit holes, bitter taste, storage rot | None effective post-infestation — harvest on time, destroy infested material | Cover ridge cracks, piecemeal harvest before 5 months, rotation, clean vines | Photograph ridge base cracks + cut root surface — leaf photos miss it |
| Sweet potato virus disease (SPVD) | Mild (crinkle) · Severe (stunting, no marketable roots) | Stunted plants, small crinkled leaves, chlorotic mottling, poor root set | None — remove infected plants, control whiteflies early | Clean certified vines only, whitefly monitoring, never save vines from infected fields | Crinkle + stunting pattern; ask about vine source in-app |
| Root-knot nematodes | Patchy (stunted patches) · Severe (galled, unsaleable roots) | Knotted galls on roots, stunted patches, roots deformed | None curative — rotation is the treatment | Rotate with cereals, avoid after tomato/potato, resistant varieties where available | Photograph washed roots — galls vs weevil tunnels are the discriminator |
| Alternaria leaf spot | Low (scattered spots) · High (defoliation in wet spells) | Brown ringed spots, yellowing, older leaves first | Registered alternaria-active classes if severe | Row ventilation, avoid overhead watering at night | Ringed spots distinguish from SPVD mottling |

---

## Cabbage (Brassica oleracea var. capitata)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Diamondback moth | Low (window-pane feeding) · High (head damage) | Leaf "windows", frass, caterpillars tunnelling into young heads | Registered larvicides at threshold only; ROTATE chemical classes (DBM resists fast) | Scout week 4–12; row covers in nursery; brassica rotation | Photo the leaf underside + inside young head; frass separates DBM from aphids |
| Black rot | Low (V-lesions) · High (systemic, head loss) | Yellow V-shaped lesions at leaf margins, blackened veins | None curative — remove plants; copper classes suppress spread | 2–3 yr brassica rotation, clean seed, no overhead irrigation | V-margin lesions are the signature (shared with kale row) |
| Downy mildew | Low (specks) · High (grey fuzz, leaf loss) | Yellow specks above, grey-purple fuzz below, cool damp spells | Registered downy-active fungicide at first specks | Spacing/airflow, morning watering | Photograph the UNDERSIDE — fuzz invisible from above |
| Head rot (soft rot) | Early (water-soaked spots) · High (collapsed heads) | Water-soaked heads, foul smell, collapse after insect damage | None — remove and destroy; manage the insect damage that opens the door | DBM/aphid control, drainage, dry harvest handling | Ask about insect damage in-app — rot follows damage |

## Spinach / Swiss chard (Spinacia oleracea / Beta vulgaris var. cicla)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Downy mildew | Low (specks) · High (grey fuzz, leaf loss) | Yellow patches above, grey-purple fuzz below, cool humid spells | Registered downy-active fungicide at first specks | Wide spacing, morning watering, resistant varieties | Underside photo required — fuzz invisible from above |
| Cercospora leaf spot | Low (scattered spots) · High (defoliation) | Round grey-brown spots with dark borders, older leaves first | Registered contact fungicide at first spots | Clean seed, rotation, remove debris | Round spots with distinct borders vs mildew fuzz |
| Aphids | Low (colonies) · High (curled, stunted growth) | Curled new leaves, colonies on undersides, sticky honeydew | Squash early colonies; registered products only if spreading | Natural enemies first — scout before spraying | Photo the leaf UNDERSIDE with the colony visible |
| Leaf miners | Low (tunnels) · High (leaf scorch, market rejection) | Silvery winding tunnels in the leaf, no holes | Remove/destroy mined leaves; registered products at threshold only | Row covers in nursery, rotation | Backlit photo shows the tunnel track best |

## Capsicum (Capsicum annuum)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Whiteflies + viruses | Low (adults present) · High (curled leaves, no fruit set) | Whitefly clouds on undersides, upward curled yellow leaves, flower drop, stunted fruit | Registered whitefly products early; remove infected plants | Sticky traps, barriers, tolerant varieties | Curling + whitefly presence = virus pattern; photograph the underside with insects |
| Bacterial wilt | Early (day-wilt) · Advanced (permanent wilt, ooze) | Green wilt in heat, recovery at night then permanent; white ooze from cut stem | None — rogue + destroy; 3-season rotation away from tomato/potato/pepper | Rotation, raised beds, no flooding | Cut-stem-in-water test is the in-app verification |
| Phytophthora blight | Early (stem lesion) · High (fruit rot, collapse) | Dark stem lesions at soil line, water-soaked fruit rot, wilting in wet spells | Registered blight-active sprays preventatively in wet weather | Drainage, raised beds, no overhead watering | Stem-base photo + fruit close-up; wet-season timing is diagnostic |
| Anthracnose fruit rot | Low (specks) · High (sunken rot on ripe fruit) | Sunken dark circular spots on ripening fruit, pink spore ooze in humidity | Registered anthracnose-active sprays from first colour | Never leave ripe fruit hanging, handle gently | Photograph the fruit, not leaves — leaf photos miss it entirely |
| Powdery mildew | Low (dusting) · High (leaf loss, sunscald fruit) | White powder on both leaf sides, yellowing, fruit exposed to sun | Sulphur-class or registered mildew products at first dusting | Airflow, avoid water stress | White powder on TOP of leaf distinguishes from downy mildew |

## Carrot (Daucus carota)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Alternaria leaf blight | Low (spots) · High (tops burn down, harvest problems) | Brown ringed spots on leaves, yellowing, burnt tops in humid weather | Registered alternaria-active sprays at first spots | Rotation, clean seed, avoid leaf wetness | Photograph the TOP growth — root photos show nothing for leaf blight |
| Powdery mildew | Low (dusting) · High (defoliation) | White dusting on leaves, weak tops, small roots | Sulphur-class products at first dusting | Row airflow, avoid water stress | White powder both sides; usually warm-dry weather |
| Root-knot nematodes | Patchy (stunted patches) · Severe (galled, forked roots) | Knotted galls on roots, forking, stunted patches | None curative — rotation is the treatment | Rotate with cereals, avoid after tomato/potato | Photograph washed roots — galls vs forking-from-stones are different causes |
| Cavity spot | Low (specks) · High (sunken holes, market rejection) | Small sunken oval holes under the skin, worse with moisture swings | None direct — correct calcium + steady watering | Balanced nutrition, consistent irrigation | Cut roots crosswise to photograph the lesions under the skin |

## Butternut (Cucurbita moschata)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Powdery mildew | Low (dusting) · High (leaf death, sunscald fruit) | White powder on leaves, yellowing, dieback in warm-dry spells | Sulphur-class or registered mildew products at first dusting | Tolerant F1s, airflow, avoid water stress | White powder on TOP of leaf, warm-dry weather — distinct from downy mildew |
| Downy mildew | Low (patches) · High (grey fuzz, defoliation) | Yellow patches above, grey-purple fuzz below, cool damp spells | Registered downy-active fungicide at first patches | Airflow, morning watering, avoid overhead irrigation | Underside photo required — fuzz invisible from above |
| Fruit fly (melon fly) | Low (stings) · High (maggots in fruit, rot) | Pin-prick stings on young fruit, oozing, maggots inside, fruit rots from inside | Registered bait sprays + weekly fruit inspection; destroy stung fruit | Early fruit protection, sanitation, remove fallen fruit | Photograph the FRUIT surface and any cut-open fruit — leaf photos miss it |
| Viruses (WMV/ZYMV/PRSV) | Mild (mottle) · Severe (distorted fruit, stunting) | Mottled distorted leaves, knobbly fruit, stunted vines; aphids present | None — remove infected plants, early aphid control | Aphid management, clean seed, tolerant varieties | Distorted leaves + aphids = virus pattern; ask about aphids in-app |
| Anthracnose | Low (specks) · High (sunken rot on fruit) | Sunken dark spots on ripening fruit, pink ooze in humidity | Registered anthracnose-active sprays in wet spells | No overhead watering, rotation, clean vines | Fruit photo needed; late-season + wet = diagnostic |

## Coriander / Dhania (Coriandrum sativum)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Damping off | Seedling (collapse patches) · Severe (stand loss) | Seedlings collapse at soil line, brown pinched stems, in wet spells | Seed-dressing where wet; no rescue for collapsed patches | Raised beds, thin sowing, well-drained nursery | Photograph a whole patch, not one seedling — pattern is the diagnosis |
| Aphids | Low (colonies) · High (curled, stunted growth) | Curled new growth, colonies on undersides, honeydew | Squash early colonies; registered products if spreading | Weekly underside scouting, natural enemies first | Underside photo with colony visible |
| Alternaria leaf spot | Low (spots) · High (leaf burn, market rejection) | Brown ringed spots on leaves, yellowing, humid weather | Registered alternaria-active sprays at first spots | Clean seed, rotation, avoid leaf wetness | Ringed spots distinguish from mildew dusting |
| Powdery mildew | Low (dusting) · High (defoliation) | White dusting on leaves in warm-dry spells | Sulphur-class products at first dusting | Airflow, avoid water stress | White powder top of leaf, warm-dry weather |

## Avocado (Persea americana)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Root rot (Phytophthora cinnamomi) | Early (yellowing, thin canopy) · Advanced (dieback, death) | Yellowing leaves, branch dieback, sparse canopy, dark lesions at the collar, wet soil | None once advanced — improve drainage, remove dying trees | Certified clean seedlings, perfect drainage, no collar watering | Photograph the tree base + canopy; ask about water pooling in-app |
| Anthracnose | Low (specks) · High (sunken rot on fruit) | Black sunken spots on fruit, often appearing days after picking, pink ooze in humidity | Registered anthracnose-active sprays pre-harvest; clean dry handling | Prune for airflow, harvest dry, handle gently | Fruit close-up; post-harvest timing of spots is diagnostic |
| Cercospora leaf spot | Low (scattered spots) · High (leaf drop, sunburned fruit) | Brown spots on leaves, yellowing, premature leaf drop in humid weather | Registered sprays at first spots | Pruning for airflow, orchard hygiene | Leaf spots + drop pattern; humid season timing |
| Thrips and scales | Low (scarring) · Severe (bronzed fruit, sooty mould) | Silver-bronze fruit scarring (thrips), dark sooty mould on leaves (scales) | Registered products at threshold only | Natural enemy preservation, monitoring | Fruit surface photo for thrips; leaf underside for scales |

## Banana (Musa spp.)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Banana weevil | Low (tunnels) · High (toppled plants, dead mats) | Larvae tunnels in the corm, dark oozing galleries, plants topple in wind | Registered treatments at threshold; trapping | Clean TC plantlets, remove old stumps, trap | Photograph the cut corm — tunnels are the diagnosis |
| Panama disease (Fusarium wilt) | Early (leaf yellowing) · Advanced (split pseudostem, death) | Yellowing from older leaves, reddish-brown staining inside the pseudostem, wilt, death; spreads on tools/suckers | None — destroy mats, biosecurity protocol | Clean TC plantlets, disinfect tools, never move soil | Internal stem staining is the signature — photograph a cut pseudostem |
| Black sigatoka | Low (streaks) · High (burnt canopy, small bunches) | Yellow-brown streaks that blacken, leaf burn from the margins, smaller bunches | Registered fungicide programme + de-leafing infected leaves | Airflow, de-leafing, resistant varieties | Streaks evolving to burnt margins; lower leaves first |
| Bunchy top virus (BBTV) | Early (upright leaves) · Severe (no bunches, stunting) | Stunted upright leaves with dark green streaks, no bunch production, aphids present | None — rogue and destroy immediately | Clean plantlets, aphid control, remove infected plants | Upright stunted leaves + streaks = BBTV; escalate — not a nutrient issue |
| Cigar end rot | Low (tip rot) · High (bunch tip loss) | Ash-grey rot at the bunch tip spreading up the fingers, wet weather | Remove the male bud; registered sprays in severe cases | Male bud removal after fruit set, airflow | Photograph the bunch tip |

## Pawpaw (Carica papaya)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Papaya ringspot virus (PRSV) | Early (mottle) · Severe (oily rings, production collapse) | Mottled distorted leaves, oily ring spots on fruit, dark streaks on stems, plants stop producing | None — rogue and destroy immediately; aphid control only | Tolerant varieties (Calina), early aphid control, clean seedlings | Ring spots on FRUIT are the signature — photograph fruit and leaves together |
| Powdery mildew | Low (dusting) · High (leaf loss) | White dusting on leaves in warm-dry spells | Sulphur-class products at first dusting | Airflow, avoid water stress | White powder both leaf sides |
| Anthracnose | Low (specks) · High (sunken rot on fruit) | Sunken dark spots on ripening fruit, pink ooze in humidity | Registered anthracnose-active sprays; clean handling | No overhead watering, harvest dry | Fruit close-up at colour break |
| Damping off / root rot | Seedling (collapse) · Advanced (wilting, death) | Seedlings collapse at soil line; established plants wilt and die in wet soil | None once advanced — drainage is the cure | Raised beds, strict drainage, no collar watering | Photograph the plant base; ask about water pooling |
| Fruit fly | Low (stings) · High (maggots, fruit rot) | Pin-prick stings on ripening fruit, larvae inside, fruit rots | Registered bait sprays; remove fallen fruit | Sanitation, early harvest | Fruit surface photo + cut-open fruit |

## Watermelon (Citrullus lanatus)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Powdery mildew | Low (dusting) · High (burnt canopy, small bland fruit) | White dusting on leaves, canopy burn before fruit fill, warm-dry spells | Sulphur-class or registered mildew products at first dusting | Airflow, avoid water stress | Canopy-level photo; pre-fill timing is the decision point |
| Downy mildew | Low (patches) · High (grey fuzz, defoliation) | Yellow patches above, grey-purple fuzz below, cool damp spells | Registered downy-active sprays at first patches | Airflow, morning watering | Underside photo required |
| Gummy stem blight | Early (stem canker) · High (vine death) | Brown cankers oozing gum on stems, wilting vines | Registered sprays at first cankers; clean rotation | Rotation, avoid wounding vines | Photograph the STEM canker — leaf photos miss it |
| Anthracnose | Low (spots) · High (sunken rot on fruit) | Sunken dark spots on fruit and leaves, wet weather | Registered anthracnose-active sprays; no overhead watering | Rotation, dry handling | Fruit + leaf photo pair |
| Fruit fly | Low (stings) · High (maggots, market rejection) | Pin-prick stings on young fruit, larvae inside, rot from within | Registered bait sprays; remove fallen fruit | Sanitation, harvest on time | Cut-open fruit photo shows the maggots |

## Snow peas (Pisum sativum var. saccharatum)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Powdery mildew | Low (dusting) · High (burnt vines, shrunk pods) | White dusting on leaves, vine burn, small pods, cool-dry spells | Sulphur-class products at first dusting — prevention beats pod loss | Airflow, resistant varieties, avoid water stress | Vine-level photo; early dusting on lower leaves first |
| Downy mildew | Low (patches) · High (grey fuzz, defoliation) | Yellow patches above, grey-purple fuzz below, cool damp spells | Registered downy-active sprays at first patches | Airflow, morning watering | Underside photo required |
| Aphids + viruses | Low (colonies) · High (curled growth, virus) | Curled new growth, colonies on undersides, mottled virus symptoms | Early aphid control only | Weekly scouting, natural enemies first | Underside photo with colony |
| Root rots | Patchy (yellowing patches) · Severe (stand collapse) | Yellowing patches, dying plants, rotting roots in wet soil | None — drainage is the cure | Raised beds, rotation away from legumes | Photograph an uprooted plant with washed roots |
| Leaf miners | Low (tunnels) · High (market rejection) | Silvery tunnels through leaves, cosmetic damage | Threshold management; remove mined leaves | Row covers, rotation | Backlit photo shows the tunnel track |

## French beans (Phaseolus vulgaris — fine/haricot type)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Rust | Low (pustules) · High (defoliation, rejected pods) | Orange-brown pustules on leaf undersides, yellow halo, defoliation | Registered rust-active fungicide at onset | Resistant varieties, avoid overhead watering | Pustules rub off on fingers — the field test |
| Angular leaf spot | Low (lesions) · High (defoliation, pod blemish) | Brown angular lesions with grey centres, on leaves and pods | Registered fungicide classes at first lesions | Rotation, clean seed, open rows | Angular vein-bounded shape discriminates vs anthracnose |
| Anthracnose | Low (specks) · High (pod rot, packhouse rejection) | Sunken dark lesions on pods, stems; black seed blotches | Registered fungicide at first sign; never save seed from infected crop | Certified seed only | Pod close-up; seed-source question in-app |
| Root rots | Patchy · Severe (collapse) | Dark rotting roots, stunted patches | None — drainage | Raised beds, rotation | Uprooted plant photo |

## Mango (Mangifera indica)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Anthracnose | Low (leaf spots) · High (flower blight, fruit rot) | Dark spots on leaves and panicles, black sunken rot on ripening fruit, wet weather | Registered anthracnose-active sprays at flowering and fruit set | Prune for airflow, remove debris, dry handling | Flower-panicle photo at bloom; fruit close-up at ripening |
| Powdery mildew | Low (dusting) · High (flower death, no set) | White powder on panicles and young fruit, flower drop, warm-dry spells | Sulphur-class products at first dusting during bloom | Pruning, airflow | Panicle-level photo during flowering |
| Mango fruit fly | Low (stings) · High (maggots, fruit drop) | Pin-prick stings on ripening fruit, larvae inside, premature drop | Registered bait sprays; orchard sanitation | Remove fallen fruit, early harvest | Cut-open fruit shows larvae |
| Sooty mould | Low (film) · High (blackened fruit, market rejection) | Black film on leaves and fruit growing on insect honeydew | Control the sap-sucking insects — the mould follows them | Scale/mealybug control | Black film + insects present = honeydew source |

## Onion (Allium cepa)

| Disease | Severity classes | Symptoms | Treatment (advisory) | Prevention | Scanner notes |
|---|---|---|---|---|---|
| Downy mildew | Low (patches) · High (leaf death, small bulbs) | Pale patches on leaves, grey-purple fuzz, cool damp spells | Registered downy-active sprays at first patches | Rotation, airflow, avoid leaf wetness | Leaf close-up; fuzz on the leaf surface |
| Purple blotch | Low (spots) · High (leaf dieback, storage rot) | Purple-brown lesions with yellow halos, leaf dieback | Registered alternaria-active sprays at first lesions | Rotation, clean debris, healthy tops | Purple-centred lesions are the signature |
| Neck rot (Botrytis) | Storage (neck softening) · Severe (bulb rot) | Soft grey rot at the neck entering storage, bulbs collapse | None post-harvest — curing is the control | Cure necks 2-3 weeks, store dry | Post-curing bulb photo; ask about curing time in-app |
| Thrips | Low (silvering) · High (stunted bulbs) | Silvery streaked leaves, stunted plants, dry spells | Registered products at threshold | Monitor, avoid drought stress | Silvery streaks on leaves |

## Process

1. **Add crops** top-down from the 20-crop queue; **add diseases** from field reports, pilot
   scanner confusions, and extension-officer feedback (the correction loop, Phase B).
2. Every treatment cell is a *class* (e.g., "registered triazole class"), never a brand —
   brands require PCPB label verification per deployment region.
3. The advisory + escalation language is mandatory in the app UI: treatment guidance is
   advisory; the extension officer sees the same photo.
4. Scanner notes feed the model's confusable-pairs training set and the photo-quality
   guidance UI (Phase A: lighting/angle prompts per disease).
