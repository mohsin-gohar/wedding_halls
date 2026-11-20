> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# PRODUCTION DEPLOYMENT READINESS — Riwaj Grand

**Date:** 2026-11-16 · **Repair cycle update: 17 Nov 2026**

## Gates
| Gate | Result |
|---|---|
| Full test suite (SQLite) | See `FINAL_ENTERPRISE_DELIVERY_REPORT.md` (authoritative final run after 17 Nov repair) |
| **Full test suite on MySQL/MariaDB** | ✅ **17 Nov**: first real MySQL run (MariaDB 10.4.32, disposable datadir, port 3307). Migrations: **0 pending** after fixing 3 real portability bugs (FK order ×2, drifted `halls` listing columns). Suite result in repair report §3. |
| Schema parity (fresh MySQL vs dev SQLite) | ✅ 80/80 tables, **0 column differences** (`schema_diff.php`) |
| Targeted new suites | 11 + 7 + 6 passed (calendar, attendance, payroll) + 4 payroll-integrity + 3 hall-listing-contract |
| `npm run build` | ✅ Vite 7.3.7 (latest run 3.07s) |
| `composer audit` | ✅ clean |
| `npm audit` | ✅ 0 vulnerabilities |
| Pint v1.24.0 (official phar; local vendor phar remains stale) | ✅ PASS 293 files |
| `php artisan migrate:status` | ✅ all Ran (incl. new repair migrations) |
| Browser QA | ✅ **17 Nov**: 5 personas (owner, front desk, chef, customer, super admin) + 4 calendar views + 360px overflow audit on 6 pages — 0 console errors |

## Blockers before commercial go-live
1. Live gateway sandbox round-trips (credentials required) — no false claim.
2. Backup + tested restore drill, rotation, encryption.
3. Error monitoring, queue/scheduler health checks, log rotation.
4. ~~Authenticated browser QA for remaining personas + mobile widths~~ — **done 17 Nov** (5 personas; 360px verified; tablet/desktop covered in prior cycles).
5. Pint tooling repair in CI (official phar works locally).
6. ~~Visual attendance/leave calendar, holidays/weekly offs, payroll-aware deductions~~ — **implemented** (see inventory).
7. ~~Hold expiry + scheduler~~ — **implemented** (`ExpireBookingHoldsCommand`, hourly).
8. **MySQL version policy**: verified on MariaDB 10.4.32 (below Laravel 12's supported minimum of MariaDB 10.6+/MySQL 8.0+); passing on 10.4 is stronger than required, but production should target MySQL 8.0+/MariaDB 10.6+.
9. **XAMPP local datadir corrupted** (unrelated project's InnoDB corruption, 15 Nov crash) — not ours, left untouched; use the disposable datadir or a fresh XAMPP reinstall for local MySQL work.

## Security posture
No secrets in reports; seeded demo credentials only in local reports (no production accounts exposed). `.env` local/dev SQLite; production must use hardened env, HTTPS, private storage for confidential documents.
