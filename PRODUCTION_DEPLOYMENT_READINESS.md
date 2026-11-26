# Production Deployment Readiness — Riwaj Grand

**Branch:** `audit/riwaj-grand-repair-20261126` · **Base:** `df8de437` · **Date:** 2026-11-26  
**No deployment was performed and no production data was touched.**

## 1. Readiness classification

| Gate | Status | Evidence |
|---|---|---|
| PHP syntax / Pint / `git diff --check` | Verified locally | 386 files linted; Pint `--test` exit 0; diff check exit 0 |
| Full MySQL 8 test suite | Verified locally | See `FINAL_ACCEPTANCE_REPORT.md` for final numbers; isolated `wedding_hall_mysql_test` only |
| Frontend production build | Verified locally (earlier round) | `npm run build` produced versioned CSS/JS assets |
| Route/view cacheability | Verified locally | Stale route cache cleared; `route:list` and `/ready` tests pass |
| Browser QA | Partially verified locally | Playwright public/RBAC/login/responsive rounds passed when rate-limit windows clear; see testing report |
| CI workflow | Configured, **not verified locally** | `.github/workflows/ci.yml` runs lint, Pint, audits, build, MySQL suite, Docker validation, browser suite; deploy job `if: false` |
| Docker container runtime | **Not verified** — no daemon on this workstation | `Dockerfile`, `docker-compose.yml`, `deploy/*` reviewed only |
| Composer/NPM advisories | Composer **blocked** (no network); NPM audit not run this pass | External-infrastructure blocker |
| Staging migration rehearsal | **Not run** | Requires owner-approved isolated MySQL 8 staging copy |
| Production deploy / secrets / HTTPS / mail / queue / scheduler / backups | **Not verified** | Owner/host-level responsibilities |

## 2. Required production settings (from templates, values not reproduced)

- `APP_ENV=production`, `APP_DEBUG=false`, generated `APP_KEY`, `FORCE_HTTPS=true`, secure/encrypted cookies.
- MySQL 8 with least-privilege DB user; `DB_ROOT_PASSWORD` only in Docker Compose context.
- `CACHE_STORE`, `SESSION_DRIVER`, `QUEUE_CONNECTION`, `BOOKING_LOCK_STORE` backed by a shared service (Redis recommended on multi-instance).
- Mail/SMTP, WhatsApp Cloud API credentials, trusted proxies, upload limits aligned across Nginx/PHP/Livewire.
- Queue worker + scheduler running under supervisor/Cron; `/up` liveness and `/ready` dependency probe monitored.

## 3. Outstanding go-live blockers

1. Run CI on the repair branch and confirm every required check is green; no workflow run exists for this head yet.
2. Fix deployment upload-limit mismatch (standalone Nginx 32m vs PHP 70m vs Livewire 50MB).
3. Decide the platform-support / wildcard-token / Livewire-throttle policy (see security report).
4. Rehearse all migrations on an isolated MySQL 8 staging copy and verify backup/restore drill.
5. Configure host-level queue worker, scheduler, HTTPS termination, secrets rotation, mail and monitoring.
6. Complete the documented financial-product decisions (hall refund UI, complimentary onboarding duration, utility `expense_id` backfill).

## 4. Verified rollback posture

- No destructive migration or data rewrite was introduced; new migration `2026_12_22_000001_add_service_booking_to_vendor_payouts.php` is additive with a guarded `down()`.
- New expense/utility migrations are guarded with `Schema::hasColumn` / `hasIndex`.
- Deployment scripts in `deploy/` include backup and restore drills that refuse production targets; not executed here.

**Status:** Code-level gates are substantially green; **production go-live is NOT approved** until the owner completes the external gates above. This is a truthful "not verified" statement, not a claim of readiness.
