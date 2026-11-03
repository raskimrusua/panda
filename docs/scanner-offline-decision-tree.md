# Offline decision-tree scan — Phase B3 design spec

> Status: **DESIGN (2026-11-03)** — implementation planned as the third Phase B
> chunk. No code shipped yet. Keep this spec honest: it documents intent,
> constraints, and the open questions the pilot must answer.

## Goal

A farmer without a connection should still get a useful (if coarser) answer.
The offline-first promise from the PWA design (service worker + idb write
queue) already covers season logs; the scanner is the missing surface.

## Non-goals

- No on-device ML inference. A web PWA cannot ship a useful vision model in
  the initial budget; the 2027-plan Phase B wording is aspirational.
- No offline PCPB catalogue beyond what's already cached.
- No offline photo capture UI changes — reuse the existing camera flow.

## Approach

**Symptom decision tree, generated from the crop JSON content layer.**
Every disease card already carries `symptoms_en` + `scanner_notes` (the
model-facing discriminator lines). The tree is a static artifact built from
those cards at build time (or a tiny PHP generator command), shipped inside
the PWA bundle — zero runtime API calls.

Shape per crop (pilot: tomato, kale, maize, pawpaw — the same 4-crop smoke
set as PR #67):

```
crop
├── Q1: where are the spots?  (older leaves / newer leaves / fruit / stems)
├── Q2: what do they look like? (rings / powder / mosaic / holes)
├── Q3: any insects visible?
└── → diagnosis key + confidence = honesty band (always ≤ 0.6, labelled "guide")
```

Questions are hand-authored from `scanner_notes` in the first pass (the
notes were written as human-readable discriminators, not structured data);
the generator command (`panda:tree:generate`) is the second pass once the
hand-authored pilot proves the shape.

## Flow

1. PWA detects offline (navigator.onLine + failed fetch).
2. Tree walk replaces the photo upload: crop pick → 2-3 symptom questions →
   diagnosis from the tree.
3. Result renders like an online scan, with `provider = 'offline_decision_tree'`,
   a fixed low-confidence label, and an explicit note: "Offline guide — confirm
   with a photo scan when you have a connection."
4. The result is written into the existing offline write queue (idb) and
   POSTed when connectivity returns.

## API additions

- `POST /api/v1/disease/offline-sync` — accepts `{crop_id?, season_id?,
  top_diagnosis, answers: {question: answer}, captured_at}`; creates a
  `DiseaseDetection` row with `provider='offline_decision_tree'`,
  `image_path = NULL`, `confidence = 0.5` (tree ceiling), and the answer
  trail in `engine_response`. No image upload. Tenant-scoped like `detect`.
- `image_path` is already nullable; `image_url` in the resource returns null
  for these rows (existing guard).

## Open questions for the pilot

1. Does the tree's top pick agree with the online scan for the same plant
   more often than chance? (Measure with the smoke-test set + real fields.)
2. Are `scanner_notes` discriminators sufficient to author Q2/Q3, or do we
   need the agronomist (Silas) to add one line per card?
3. Tree confidence labelling — fixed 0.5 vs per-branch honesty band?
4. Offline-sync row rate-limits (Rule 60 posture: 1 in-flight + active caps
   don't apply, but the sync endpoint needs a sane daily cap per tenant).

## Out of scope forever (until explicitly re-planned)

- On-device vision inference (TF.js / WebML) — revisit only if the tree's
  accuracy proves unusable AND a model with < 15 MB footprint exists.
