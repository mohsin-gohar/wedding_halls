# Documentation authority map

This repository previously accumulated duplicate audit reports, AI-agent
summaries, obsolete prompts and superseded roadmaps. Current project guidance
is intentionally kept small. Superseded documents were removed from the active
tree after confirming they were historical; their original contents remain
recoverable from Git history (the cleanup commit's parent).

## Current source of truth

| Topic | Authoritative file |
|---|---|
| Project overview, setup and architecture entry point | `README.md` |
| Current architecture, workflows, audit ledger and residual code risks | `FINAL_REPOSITORY_AUDIT.md` |
| Implemented features and verified regression coverage | `IMPLEMENTATION_COMPLETION_REPORT.md` |
| Go-live gates and infrastructure blockers | `PRODUCTION_READINESS_REPORT.md` |
| Final CI, test counts and acceptance evidence | `FINAL_ACCEPTANCE_REPORT.md` |
| File inventory and review coverage (not a claim that every file was manually read) | `FILE_BY_FILE_REVIEW_MATRIX.md` |
| Financial schema and ledger integrity | `DATABASE_AND_FINANCIAL_INTEGRITY_REPORT.md` |
| Tenant isolation and authorization | `SECURITY_AND_TENANT_ISOLATION_REPORT.md` |
| Business workflows and modules | `BUSINESS_MODULE_COMPLETION_REPORT.md` |
| Browser and automated QA evidence | `TESTING_AND_BROWSER_QA_REPORT.md` |
| Cash-only money handling | `CASH_ONLY_PAYMENTS.md` |
| Public API contract | `API_DOCUMENTATION.md` |
| Deployment and operations | `HOSTINGER_DEPLOYMENT_GUIDE.md`, `deploy/*.md` |

## Historical documents

Superseded audit, roadmap, AI-agent prompt and implementation-summary files
were removed from the current working tree. Their contents remain in earlier
Git commits and can be recovered with `git show <commit>:<old-path>` when a
specific historical detail is genuinely needed. Do not recreate those files
or treat their old test counts/payment claims as current instructions.

Update the authoritative documents above when project behaviour changes. Do
not create another top-level audit/summary report for routine work.

## Financial-policy correction

Cash is the only accepted method for every new financial write. Historical
rows keep their original labels for audit continuity. Customer payment
submissions remain `PENDING`; only authorised staff verify physical cash.
