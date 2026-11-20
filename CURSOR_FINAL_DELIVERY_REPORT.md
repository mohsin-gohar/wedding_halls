> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# Final delivery

> **Repair-cycle banner, 17 Nov 2026 — READ FIRST.** This report described the state at Cursor's HEAD `0cb426a` and is now **historical**. Three concerns raised in review are fixed and verified in the repair cycle (see `FINAL_ENTERPRISE_DELIVERY_REPORT.md` §0, authoritative): (1) DRAFT payroll can no longer be paid — `markAsPaid()` enforces APPROVED inside a transaction; (2) payroll payment is atomic (status + expense + audit in one `DB::transaction` with `lockForUpdate`, unique `expenses.payroll_id`); (3) the 360px payroll overflow is fixed and verified on 6 pages. Additionally, MySQL **is now actually verified**: on MariaDB 10.4.32 (disposable datadir, port 3307) `migrate:fresh` runs clean after fixing 3 real FK-order/schema-drift bugs that SQLite silently tolerated; the full suite was re-run on MySQL. `PayrollPaymentIntegrityTest` (4) and `HallListingContractTest` (3) pin the fixed contracts. Reports below keep their original wording as historical record.

Work continued from `enterprise-hardening` HEAD `d9a9b93`. Existing committed work was not reset. New code is uncommitted.

## What was executed

- Hardcoded payroll role salaries removed. A new payroll uses the employee base salary or an explicit policy salary. Staff with neither are skipped and named in the payroll alert. No amount is invented.
- Booking calendar now has month, week, day, and agenda views, tenant shift windows, a turnover buffer, and hourly hold expiry. Holds with no expiry timestamp are left alone.
- Regression tests: `PayrollSalarySourceTest`, `CalendarViewsAndHoldExpiryTest`, and the Kashif payroll fixture now carries a real salary of 38000.
- Complete suite: `php artisan test --compact` → **231 passed, 1 skipped, 1,334 assertions, duration 542.91s, exit 0**.
- Pint `--test` passed. Composer audit found no advisories. npm audit reported 0 vulnerabilities. Vite build succeeded (CSS 199.38 kB, JS 53.11 kB).
- Local SQLite migration applied. Backup copy of that database was opened (`users=12`, `halls=3`, new shift column present) and then deleted from the working tree.
- Browser: hall owner logged in on `http://127.0.0.1:9000`, dashboard loaded, calendar week view switched, payroll policy form rendered. Compile Payroll was not submitted against live data.

## Not done, with the evidence

- MySQL tests: `mysql.exe` returned `ERROR 2002 (HY000)` / Windows 10061. Nothing was run on MySQL.
- Payment sandbox: `payment_gateways` has 0 rows and the env file has no Stripe, JazzCash, or PayFast keys.
- Persona matrix: customer, super admin, finance, vendors, and the other staff roles were not signed in this pass.
- Responsive matrix: 1920-class calendar had no page overflow. 360px payroll still overflows (543 vs 360). 390, 430, 768, 1024, and 1366 were not measured.
- Local `.env` still has debug on and HTTPS off. Login still shows demo-account buttons.

This is not a 100% completion claim.
