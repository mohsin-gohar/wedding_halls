# Production Readiness Report (authoritative)

> This is the go/no-go evidence ledger for the current pass. Historical
> readiness reports are snapshots and are superseded; see `DOCS_STATUS.md`.



## 26 Nov 2026 follow-up — post-baseline changes not yet CI-verified

The following repository changes were added after the recorded verification baseline:
- Vendor service pricing now rejects non-finite/negative amounts, reversed ranges, and missing/non-positive PER_HEAD/HOURLY base prices; regression tests were added.
- API booking/service-booking list and detail endpoints now require explicit read permissions; Finance and Content Moderator no longer inherit global booking visibility. Regression tests were added.
- WhatsApp webhook and simulator now require explicit RSVP intent; unclear messages receive clarification and leave RSVP state unchanged. Regression tests were added. Outbound WhatsApp requests now have a 10-second timeout and provider HTTP failures are logged; the fallback Graph API version is v26.0.
- Vendor quote boards now expose only the signed-in vendor's own bid, never competitor amounts/identity; modal prefill is similarly scoped. Bid submission locks and rechecks that the RFQ is still OPEN, with regression tests for confidentiality and closed-RFQ rejection.

Latest code follow-up commits checked: `bef3966cd582f943165e3c60c4d9f54c8f797b3e`, `5cade99da456b29bdd4e6f6150c3459784f3e617`, `6f01c263e1e662002f2bae2f68d5ea096f94d97b`, `36bb0d4b63e3929ce648102b82ef93a563617ee4`, `7044976a9acc95acd9325e29f2b97383df27982c`. GitHub currently reports no workflow run or status checks for this head. Therefore the historical test/build/browser results below apply only to their recorded baseline and **must not be represented as verification of these follow-up changes** until CI is run successfully.

## 1. Decision summary

Local code, test, build and browser gates for the Phase 1–9 / cash-only scope
are being executed and recorded below. **No production deployment and no
production database change was performed.** A production go-live still requires
the host-level checklist in section 5.

## 2. Final verification ledger

| Gate | Command / evidence | Result |
|---|---|---|
| Targeted regression (8 files) | `phpunit -c phpunit.local.xml <8 hardening/smoke files>` | **PASS — 63 tests, 321 assertions, exit 0** |
| Full MySQL suite | `phpunit -c phpunit.local.xml` | **PASS — 444 tests, 2,602 assertions, 1 skipped, exit 0** |
| Responsive browser proof | `npm run test:browser` | **PASS — 8 tests, 8 passed, exit 0** |
| PHP syntax | `php -l` on changed non-Blade PHP files | **PASS — 85 files** |
| Code style | `pint --test` on changed non-Blade PHP | **PASS** |
| Whitespace / patch hygiene | `git diff --check` | **PASS — only pre-existing CRLF warnings** |
| Route registration | `php artisan route:list --except-vendor --json` | **PASS — 108 routes** |
| Blade compilation | `php artisan view:cache` | **PASS** |
| Front-end build | `npm run build` | **PASS — `app-B6jCqcvY.css`, `app-DThEJBru.js`** |
| Secret scan | repository pattern scan | **PASS — no configured secrets found** |
| Clean migration + seed | `migrate:fresh --seed` on local MySQL | **PASS — 3 QA users, 3 halls, 7 tenants** |
| Previous cash-only CI | GitHub Actions run `44723054819` | **GREEN** — https://github.com/mohsin-gohar/wedding_halls/actions/runs/44723054819 |

The authoritative output files for this pass are kept outside the repository in
`F:\opencode-scratch-archive\` (`full_suite_clean.out/.exit`,
`browser_qa_clean2.out/.exit`, `targeted_fixes_*.out` and the earlier
`targeted_regression_final.out/.exit`). A later full-suite confirmation run was
cancelled by a workstation/server restart and is deliberately not counted. The
final two Blade layout fixes and the Playwright helper refinement were verified
by a live DOM overflow sweep and the complete 8/8 browser suite.

## 3. Browser proof scope

`tests/browser/responsive-proof.spec.ts` visits seven public routes at
**320, 360, 390, 768, 1024, 1440 and 1920 px** and captures:

- HTTP response status and page errors from the browser console,
- failed/4xx-5xx network responses,
- `document.documentElement.scrollWidth` vs `window.innerWidth`,
- the first five horizontal-overflow offenders when present (controls inside a
  genuine horizontal scroll container, such as the vendor category strip, are
  excluded),
- Playwright trace/screenshot/video retained on failure.

The final proof pass also fixed two clipped mobile controls found by this spec:
the `/venues` hall filter and the `/check-availability` shift cards. Final
pass/fail and artifacts are recorded in the ledger above. A route is not claimed
as proven unless the spec actually executed for that viewport.

## 4. Residual risk and infrastructure limits

- Online payment gateways are intentionally removed; cash reconciliation is a
  manual operational control that must be documented for finance staff.
- Lighthouse and synthetic load tooling are not installed in this environment.
  If they remain unavailable, this report records them as **not run** rather
  than inventing scores.
- Redis, queue workers, scheduler, mail, HTTPS termination and backups are
  host-level concerns not verifiable from this workstation.
- No production deploy is authorized in this change; CI deployment remains
  disabled (`if: false`).

## 5. Host go-live checklist (still required)

1. Set unique `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, trusted hosts,
   HTTPS URL and secure session/cookie settings.
2. Provision MySQL 8 with the configured database/user; run `php artisan migrate
   --force` and verify the conditional KOT index result in logs.
3. Configure Redis for cache/session/queue where used; start and supervise the
   queue worker and scheduler.
4. Configure mail, WhatsApp webhook secret and storage (`public` disk link).
5. Run `npm run build`, `php artisan config:cache`, `route:cache`, `view:cache`,
   `php artisan optimize` in the release step.
6. Verify monitoring, backups and a documented cash reconciliation procedure.
7. Re-run the full test suite and browser proof against the staged host before
   switching traffic.
