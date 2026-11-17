# Production readiness

## 17 Nov 2026 checks

| Check | Result |
| --- | --- |
| `php artisan test --compact` | 231 passed, 1 skipped, 1334 assertions, 542.91s, exit 0 |
| `vendor/bin/pint --test` | passed |
| `composer audit` | no advisories |
| `npm audit` | 0 vulnerabilities across 161 dependencies |
| `npm run build` | succeeded |
| `migrate:status` | new salary/shift/hold migration Ran on local SQLite |
| MySQL suite | blocked, server not listening |
| Backup restore | local SQLite copy opened and counted, then removed from the tree |
| Payment sandbox | blocked, no credentials |
| HTTPS / debug | local `.env` still debug on and HTTPS not forced; do not deploy that file |

Not production-ready.

| Check | Status |
| --- | --- |
| `php artisan test` | Last full run: 222 passed, 1 failed, 1 skipped. The failure was fixed and retested alone. Full suite not repeated after that one-line change. |
| `pint`, `composer audit`, `npm audit`, `npm run build` | Not run in this session. An earlier `npm run build` on this repo succeeded before these edits. |
| `APP_DEBUG` | Local `.env` has debug on. Do not deploy that file. |
| HTTPS / HSTS | `FORCE_HTTPS` is false locally. |
| Backup restore | Not performed. |
| Payment sandbox | Not performed. |
| Hold expiry scheduler | Not implemented. |

Demo logins on the sign-in screen must be removed before a public deploy.
