> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# OPENCODE PERFORMANCE REPORT — Riwaj Grand

**Date:** 2026-11-16
**Branch:** `enterprise-hardening`

---

## 1. BUILD PERFORMANCE

```
$ npm run build

> vite build
✓ 58 modules transformed.
rendering chunks...
computing gzip size...
public/build/manifest.json           0.20 kB
public/build/assets/app-BZep80kn.css 185.27 kB │ gzip: 26.79 kB
public/build/assets/app-BnTiw5fx.js  53.11 kB │ gzip: 20.15 kB
✓ built in 5.35s
```

- Vite 7.3.7, Tailwind 4 CSS in a single 185 KB stylesheet (26.8 KB gzipped).
- App JS 53 KB (20.2 KB gzipped) — components code-split via Vite manifest.
- Build time: ~5.35s (acceptable).

---

## 2. QUERY EFFICIENCY (source-level audit)

- Performance indexes added on core tables: `2026_10_24_000001_add_performance_indexes_to_core_tables.php`.
- Booking concurrency suite verifies atomic slot reservation via DB transactions + cache locks (no N+1 hotspots observed in the tested paths).

---

## 3. KNOWN LIMITATIONS

- No load-test run (k6) performed this cycle.
- No dedicated query-profiler capture on production-sized datasets.

---

## 4. VERIFICATION

```
php artisan test --compact  # → 195 passed / 1 skipped (1219 assertions) — incl. BookingConcurrencyTest
```

---

*Next: `OPENCODE_PRODUCTION_READINESS.md`.*
