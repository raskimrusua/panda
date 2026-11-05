# Panda IoT — Phase 1 guided install design (no hardware integration)

Status: DESIGN SPEC — not built. Resume keywords: "panda IoT phase 1", "guided install".
Follows the plan doc §5c. Scanner Phase A/B (PRs #71-73) and the price feed (PR #74) are the
adjacent shipped systems.

## Why

The plan doc (§5c) phases IoT as:
- **Phase 1**: guided assembly/install/use — offline checklists, wiring diagrams (print-friendly),
  vendor links, manual sensor-reading entry. No hardware integration. Ships fast.
- **Phase 2**: gateway ingestion — ESP32/Pi gateway posts JSON to Laravel (devices + telemetry),
  threshold alerts, plot dashboards. Pilot with 1-2 farms.
- **Phase 3**: vendor integrations + device marketplace.

Phase 1 exists to answer the demand question BEFORE any hardware engineering: do farmers
actually assemble and use a KES 1,500-node class soil-moisture kit when a phone walks them
through it? The app teaches, the farmer builds, the reading goes in by hand. Every step is
reusable later by Phase 2's onboarding.

## What exists that Phase 1 builds on

- The blog content (20 crop guides + vertical-farming cluster + IoT explainer series) — the
  curriculum already exists in the marketing repo; Phase 1 makes it interactive.
- The PWA is offline-first (service worker + offline storage patterns already proven) — the
  checklists and diagrams MUST work offline; a field with no signal is the exact place they
  are used.
- Print CSS on the PWAs (the pwa-print-css track, 2026-11-02) — the "print the wiring diagram"
  use case is already live on Shira + Panda. Phase 1 reuses it.
- `MarketPrice` manual entry precedent — the app already accepts manual observations
  (admin_csv / field_agent sources); manual sensor readings are the same shape, tenant-scoped.

## Phase 1 surface (PWA routes + API)

1. **Guides library** — `pwa/src/pages/iot/*`: one guide per kit. Kit 1 = the soil-moisture +
   temperature probe from the blog's KES 1,500-node class (Arduino/ESP8266 + YL-69-class probe
   + 18650 power — the exact part list exists in the soil-sensors blog post).
   - Checklist steps (assemble → wire → power → test → install in field), each step:
     title, detail, photo/diagram, "done" toggle, trouble link.
   - Wiring diagram: static SVG/PNG, print-friendly (reuse the print CSS discipline:
     `print:hidden` on chrome, exact-color diagram block).
   - Vendor links: curated list per part (the blog posts carry Nov-2026 price ranges —
     links verified before ship; PCPB discipline doesn't apply to electronics but the
     "verify before printing" habit does).
2. **Offline**: guides cached for offline (they are static content — cache on first open).
   Progress (checked steps) persists locally and syncs when online (same pattern as the
   Phase B offline decision tree's sync queue).
3. **Manual reading entry** — tenant-scoped:
   - New table `sensor_readings` (migration): `id`, `tenant_id`, `crop_id?`, `sensor_type`
     (soil_moisture, temperature, ph...), `reading_value`, `unit`, `recorded_at`,
     `recorded_by`, `notes`, `created_at`. Append-only, mirrors market_prices discipline.
   - `POST /api/v1/sensor-readings` (auth:sanctum, tenant middleware) + `GET /api/v1/sensor-readings`.
   - PWA: simple "log a reading" form on the guide page + a readings list per crop.
4. **Threshold nudge (advisory, no alerting yet)**: when a manual soil-moisture reading is
   below the crop's guide threshold, the app shows the same advisory framing as the disease
   scanner ("Your maize at week 6: soil moisture reading is low — the guide recommends
   watering now. Advisory — confirm with your own check."). No push notifications in Phase 1
   (that's Phase 2 with real devices).

## Honest boundaries (state in the guide footer)

- These are assembly guides for hobby-grade kits; they are NOT weather stations. Readings
  are point samples, not field truth.
- No warranty on parts; vendor links are suggestions, not endorsements.
- The kit costs ~KES 1,500 in parts and an afternoon; it saves a dry-soil guess, not a drought.

## Schema sketch

```sql
-- migration: create_sensor_readings_table
CREATE TABLE sensor_readings (
    id uuid/ulid PRIMARY KEY,
    tenant_id FK tenants,
    crop_id FK crops NULL,           -- optional crop context
    sensor_type TEXT NOT NULL,       -- soil_moisture | temperature | ph | ...
    reading_value NUMERIC(8,2) NOT NULL,
    unit TEXT NOT NULL,              -- percent | celsius | ph
    recorded_at TIMESTAMP NOT NULL,  -- when the farmer READ it (not when it synced)
    recorded_by FK users NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NOT NULL
);
INDEX (tenant_id, recorded_at);
```

## Success metric (the gate to Phase 2)

- N farms complete a guide end-to-end (all steps checked) and log ≥ 14 consecutive daily
  readings on one crop. N=3 pilots is the house threshold (Rule 23 discipline) before
  building the Phase 2 gateway.
- If completion stalls at the parts-sourcing step, the answer is a parts KIT offer (vendor
  partnership), not more software.

## Out of scope (explicitly)

- Device firmware, gateway hardware, LoRaWAN, vendor APIs, push notifications, billing.
- Automating irrigation. The guide may mention solenoid valves (the blog covers them) but
  Phase 1 never controls anything.

## Suggested build order (each a small PR)

1. Migration + sensor_readings API + tests (mirror MarketPrice patterns).
2. Guide content model (static TS data from the blog posts) + PWA guide page + offline cache.
3. Wiring diagram asset + print styling.
4. Manual entry form + readings list + threshold nudge.
5. Field pilot with 1-2 farms; measure against the success metric.
