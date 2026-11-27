# Production Readiness Report (authoritative)

> This is the go/no-go evidence ledger for the current pass. Historical
> readiness reports are snapshots and are superseded; see `DOCS_STATUS.md`.



## Current verification snapshot (2026-11-27)

The latest GitHub-verified source baseline before documentation cleanup is
`1b57773f8add34f515b92995b54a812f6d060cdf`. CI run
[45982335954](https://github.com/mohsin-gohar/wedding_halls/actions/runs/45982335954)
passed MySQL (609 passed, 3 skipped, 3,153 assertions), Playwright (8 passed),
and all static/build/security/Docker gates. Deployment was intentionally
skipped. Older test counts and incomplete workflow states below are historical.

## 1. Decision summary

The latest exact-head GitHub CI evidence is recorded in section 2. No production
deployment or production database change was performed. A production go-live
still requires the owner-controlled staging and host checklist in section 5.

## 2. Final verification ledger

The latest exact-head GitHub Actions run on source HEAD
`1b57773f8add34f515b92995b54a812f6d060cdf` is
[45982335954](https://github.com/mohsin-gohar/wedding_halls/actions/runs/45982335954).

| Gate | Evidence | Result |
|---|---|---|
| Full MySQL suite | GitHub CI MySQL test job | **PASS — 609 passed, 3 skipped, 3,153 assertions** |
| Browser QA | GitHub CI Playwright job | **PASS — 8/8 tests** |
| PHP lint / Pint | GitHub CI static quality job | **PASS** |
| Composer security audit | GitHub CI static quality job | **PASS — 0 advisories** |
| NPM audit | GitHub CI static quality job | **PASS — 0 vulnerabilities** |
| Vite production build | GitHub CI static quality job | **PASS** |
| Docker config/build/smoke | GitHub CI static quality job | **PASS** |
| Deploy | Workflow deploy job | **SKIPPED — manual-only by design** |

Local inspection of the uploaded ZIP also passed PHP syntax lint on all 477
tracked PHP files and `php artisan route:list` (109 registered routes). Local
Pint/PHPUnit could not run because this runtime lacks `mbstring`, `xml`, `dom`
and `xmlwriter`; the bundled Rollup native optional dependency was Windows-only.
The CI run above is the authoritative test/build evidence for the source head.

The following repository cleanup is documentation/artifact-only and does not
change application runtime code. A new CI run is required on its resulting
commit before that exact commit is considered verified.

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
