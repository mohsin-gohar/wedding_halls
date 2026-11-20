> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# PERFORMANCE & SCALABILITY REPORT — Riwaj Grand

**Date:** 2026-11-16

## Build (measured)
```
npm run build
vite v7.3.7 — 58 modules transformed — built in 5.35s
app CSS 185.27 kB (gzip 26.79 kB) · app JS 53.11 kB (gzip 20.15 kB)
```

## Query-level review this cycle
- **Calendar**: removed redundant `whereRaw('date(event_date) between ? and ?')` — the `whereBetween` on a DATE column is index-friendly (`idx_hall_event_slot`, `tenant_id+event_date`); the removed function call was driver-coupled and duplicated work.
- **Calendar grouping**: one query per render, grouping in PHP (O(n) for one month of one hall) — no per-cell queries; no N+1 introduced.
- **Attendance save**: per-row `first()`/`update()` loop now wrapped in a single `DB::transaction()` — atomic, but still O(rows) queries; acceptable for staff rosters, revisit with bulk upsert at large scale.
- **Approved leave**: one `first()` + create/update per day — bounded by leave length.
- Existing performance indexes: `2026_10_24_000001_add_performance_indexes_to_core_tables.php`, `2026_10_28_000001_add_booking_foreign_key_indexes.php`.

## Not measured
- k6 / load tests at 50 concurrent booking attempts (planned).
- Lighthouse scores (tool available; not run this cycle).
- Production-sized dataset query timings.

## Commands
```
npm run build                 # green, 5.35s
php artisan test ...          # green (see FINAL_ENTERPRISE_DELIVERY_REPORT.md)
```
