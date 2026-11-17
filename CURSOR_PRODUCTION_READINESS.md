# Production readiness

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
