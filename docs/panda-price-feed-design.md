# Panda price feed — design

Status: v1 shipped (import command + CSV adapter); harvest-window alerts = slice 2 (spec below).
Resume keywords: "panda price feed", "market prices", "AMIS importer".

## Why

The plan doc (§5d) calls for a "daily feed (honest framing: daily wholesale prices, refreshed
each morning — not intraday)" normalizing KAMIS/NAFIS/RATIN into `market_prices`, plus
harvest-window alerts tied to season records. The `MarketPrice` model, public API endpoints
(`/api/v1/prices/{crop}/latest|history|forecast`), Filament admin and the PWA price page already
exist — what was missing is INGESTION. This closes that gap.

## Source landscape (reality check, 2026)

- **AMIS** (MoALD Agricultural Marketing Information System, formerly KAMIS) — county wholesale
  bulletins, daily-ish. Primary source.
- **NAFIS** (National Farmers Information Service) — county price pages; overlaps AMIS.
- **RATIN** (ratin.net, EAGC) — East-Africa cross-border prices; useful later for export crops.
- None publishes a stable machine-readable API with a public contract. The pragmatic v1: a
  **normalized CSV contract** + adapter pattern. Any source (downloaded bulletin export, partner
  feed, manual admin export) becomes an importer run once it emits the contract.

## CSV contract

Columns (header row required):

| column | required | notes |
|---|---|---|
| crop | yes | crop name — mapped via alias map to a catalogue slug |
| market | yes | market name, e.g. `Marikiti (Nairobi)` |
| county | yes | county name — AMIS bulletins are county bulletins |
| date | yes | `Y-m-d` — the OBSERVED date (when the price held) |
| grade | no | grade text, e.g. `Grade 1`; defaults to `standard` (the DB default) |
| price_per_kg | yes | KES per kg, numeric |

One row = one (crop, market, date, grade) observation. The adapter's job is to normalize source
units (bag, crate) into per-kg BEFORE the importer sees the rows; v1 rejects non-numeric prices
rather than guessing.

## Importer behavior

- **Append-only, idempotent**: rows whose (crop_id, market_name, observed_at, grade, source)
  tuple already exists are skipped. Re-running the same file inserts nothing new. The importer
  NEVER edits or deletes existing rows (a wrong row is superseded by a newer one — see the model
  docblock).
- **Crop mapping**: crop name → slug via `CropAliasMap::toSlug()` (exact, then alias table with
  the AMIS commodity names, then suffix-trimmed fuzzy). Unmatched crops are skipped and reported —
  never silently dropped (the report lists them).
- **Flag discipline** (house pattern): `PRICE_FEED_ENABLED=true` + `PRICE_FEED_URL` required for
  URL fetch; unset = the command reports not-configured and exits 0. `--file=` always works for
  manual/admin drops (no external call).
- **Report**: imported / skipped / unmatched counts + a sample of unmatched names — the
  alias-map feedback loop (run the report, extend the map).

## Scheduling

Laravel 11 — `bootstrap/app.php` gains `->withSchedule()` with
`$schedule->command('panda:import-prices')->dailyAt('05:00')->withoutOverlapping();`
The command itself is flag-gated, so the schedule is inert until `PRICE_FEED_ENABLED=true`.
05:00 EAT = 02:00 UTC-ish before first coffee; hour is config-adjustable later.

## Slice 2 — harvest-window alerts (spec, not built)

When a season's current timeline position enters the crop's harvest window (from the crop content
`timeline_template`, activity_type=harvest rows), alert the tenant with that week's latest prices:
- New table `price_alerts` (tenant_id, season_id, crop_id, window_week, latest_price snapshot,
  sent_at) or reuse a notifications table if one lands first.
- `panda:check-harvest-windows` scheduled daily after the import.
- Alert copy EN/SW, advisory framing, price + disclaimer from `legal.price_disclaimer`.
- Dedupe: one alert per season per harvest window.

## Open questions

- AMIS bulletin export format changes seasonally — the adapter will need maintenance runs;
  keep the alias map and column mapping in ONE file (`PriceCsvImporter` constants) for cheap edits.
- RATIN integration when an export-crop pilot (avocado/snow peas) signs.
