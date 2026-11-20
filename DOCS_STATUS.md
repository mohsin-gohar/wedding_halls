# Documentation authority map

This repository accumulated many audit, summary and roadmap files from earlier
passes. Some still describe online gateways, old test counts, old branding, or
verification that was never actually run. They are preserved as historical
context, but they are **not** the current source of truth.

## Current authoritative documents

| Topic | Authoritative file |
|---|---|
| Full repository audit ledger, architecture map, workflow map | `FINAL_REPOSITORY_AUDIT.md` |
| What was actually implemented and verified | `IMPLEMENTATION_COMPLETION_REPORT.md` |
| Go-live gates, residual risk, infrastructure limits | `PRODUCTION_READINESS_REPORT.md` |
| Cash-only financial policy | `CASH_ONLY_PAYMENTS.md` |
| Public API contract | `API_DOCUMENTATION.md` |
| Project overview and local setup | `README.md` |
| Deployment notes | `HOSTINGER_DEPLOYMENT_GUIDE.md`, `deploy/*.md` |

## Superseded historical documents

Every other top-level `*.md` file that does not appear in the table above is
**historical/superseded**. It may contain useful investigation notes, but any
claim about current behaviour, test counts, browser proof, payment methods or
production readiness must be ignored in favour of the authoritative files.

A banner is prepended to those historical files so this status is visible when
they are opened. No historical evidence was deleted or rewritten.

## Financial-policy correction

Cash is the only accepted method for every new financial write. Historical
rows keep their original labels for audit continuity. Customer payment
submissions remain `PENDING`; only authorised staff verify physical cash.
