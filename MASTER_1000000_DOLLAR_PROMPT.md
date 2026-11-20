> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# MASTER $1,000,000 WEBSITE BUILD PROMPT
## EventHub / Wedding_hall — Laravel 11 + Livewire 3 + Tailwind v4 + Alpine.js

Use this as the single source of truth for transforming the project into a $1,000,000-grade, production-ready, premium hospitality SaaS marketplace.

## 0. Mission
You are a senior full-stack architect + product designer + security engineer + QA lead. Make this repository world-class, enterprise-grade, conversion-optimized, accessible, secure, scalable, and beautiful. Optimize for revenue, trust, speed, and maintainability.

## 1. Non-Negotiable Rules
1. Preserve all existing passing tests; never weaken assertions.
2. Run vendor/bin/pint, php artisan test, npm run build after every phase.
3. No secrets in repo; use .env.example with safe defaults.
4. Keep multi-tenant isolation strict; tenant_id must never leak.
5. All destructive actions require confirmation + audit log.
6. Every public page must render 200 for guests; protected routes must redirect/403.
7. Prefer small, reviewable commits; update docs.
8. Fix root causes, not symptoms.

## 2. Confirmed Bugs / Gaps To Fix First
### Critical / High
1. .env.example defaults to SQLite; production should default to MySQL.
2. public/storage symlink may be stale; uploaded media can 404.
3. Hardcoded calendar years [2026..2030] in PublicVenues and PublicHallDetails; make dynamic.
4. Admin dashboard aggregate queries can become slow; cache/materialize.
5. Audit super-admin bypass paths for tenant isolation.
6. Ensure all Livewire components enforce auth/role in mount() and actions.
7. Ensure CSRF, rate limiting, validation on every form/webhook.

### Medium
8. Add DB indexes: halls.slug, tenants.verification_status, bookings.event_date, bookings.hall_id, bookings.tenant_id.
9. Add HTTP cache headers / ETag for public pages.
10. Add lazy loading, srcset, WebP image pipeline.
11. Add sitemap.xml, robots.txt, OG/Twitter meta, JSON-LD EventVenue, canonical URLs.
12. Improve empty/error states with CTAs.
13. Add skeleton loaders for Livewire loading states.

### Low / Polish
14. Replace Pinterest hotlinked hero image.
15. Verify UTF-8 without BOM in all blade files.
16. CSP nonce-based refinement.
17. Add focus-visible and modal ARIA attributes everywhere.

## 3. Security Requirements
- Enforce HTTPS in production.
- Keep SecurityHeaders middleware.
- Rate limit auth, contact, RSVP, availability, webhooks.
- 2FA for admin + vendor.
- Audit log all sensitive mutations.
- SQL injection/XSS audit clean.
- Strict $fillable.
- Tenant isolation tests for every tenant-scoped model.

## 4. Backend Logic Requirements
- Booking concurrency prevention with DB locks/transactions.
- Full-day bookings expand to all shifts.
- Availability checker scalability.
- Subscription lifecycle correctness.
- Staff payroll/attendance accuracy.
- Inventory transactional.
- WhatsApp webhook signature verification and idempotency.
- Tenant-safe document generation.

## 5. UI/UX Theme Requirements
- Keep luxury obsidian/champagne/ivory design language.
- Cinematic hero, real imagery, strong CTAs.
- Mobile-first, fast, accessible.
- Dark mode polished.
- Empty/loading/error states.
- Smooth micro-interactions with reduced-motion respect.
- Premium typography hierarchy and spacing.
- No broken images/layout shifts.

## 6. Accessibility (WCAG 2.1 AA)
- Skip link, keyboard access, ARIA labels, form labels/errors, modal semantics, contrast, focus-visible, screen-reader friendly tables.

## 7. SEO / Marketing
- sitemap.xml, robots.txt, meta, JSON-LD, canonical URLs, Core Web Vitals.

## 8. Performance Targets
- Lighthouse mobile >= 90 all categories.
- Core Web Vitals: LCP < 2.5s, CLS < 0.1, INP < 200ms.
- p95 < 1s under 100 concurrent users.
- No N+1 on public pages.

## 9. Phase Plan
Phase 1 — Stabilize & Secure
Phase 2 — Performance & SEO
Phase 3 — Premium UI/UX
Phase 4 — Advanced Features
Phase 5 — QA & Launch

## 10. QA Gates
- php artisan test all green (target 120+).
- vendor/bin/pint --test no violations.
- npm run build success.
- Lighthouse >= 90 on 5 key pages.
- axe-core 0 critical.
- No console errors.
- No broken images/404.
- All forms validate.
- Destructive actions confirmed.
- HTTPS enforced.
- Security headers present.
- CSRF on all forms.
- Rate limiting.
- 2FA available.
- No secrets in repo.
- SQLi/XSS clean.
- Backups, queue/scheduler, Sentry, uptime monitoring, runbook/rollback.

## 11. Deliverables
- Updated codebase.
- 120+ tests.
- Updated docs.
- Lighthouse/axe reports.
- Production .env checklist.

## 12. Execution Instruction
Start Phase 1. For each change: identify file/root cause, implement minimal correct fix, add/adjust tests, run Pint/tests/build, document. Do not declare completion until all QA gates pass.
## 14. Visual QA / Local Runtime Verification (MANDATORY AFTER ALL TASKS)

After all code/test/build phases are complete, you MUST run the project locally and perform a visual UI audit before declaring completion.

### 14.1 Local Run
1. Ensure `.env` is configured for local (APP_ENV=local, APP_DEBUG=true).
2. Run database migrations and seeders:
   - `php artisan migrate:fresh --seed`
3. Start backend:
   - `php artisan serve`
4. Start frontend assets:
   - `npm run dev`
5. Open the app in a browser at `http://127.0.0.1:8000`.

### 14.2 Visual Inspection Checklist
Inspect every public and protected page manually:
- Home
- Venues marketplace + venue detail
- Menus, packages, gallery, services, about, contact, FAQs
- Availability checker + RSVP
- Legal pages
- Login/register (vendor + customer)
- Vendor dashboard + all ERP modules
- Customer dashboard
- Super admin dashboard

For each page verify:
- No blank screens or console errors.
- No broken images or 404 assets.
- All buttons clickable and wired to correct actions.
- All links navigate to intended routes.
- Forms submit successfully and show validation messages.
- Modals open/close correctly.
- Tables/charts render.
- Dark/light mode toggle works.
- Mobile responsive layout works.
- No layout overflow/horizontal scroll.
- No inaccessible color contrast issues.

### 14.3 Fix Loop
If any UI, button, link, form, modal, image, layout, or interaction issue is found:
1. Identify exact file/component/route.
2. Fix root cause.
3. Re-run `vendor/bin/pint`, `php artisan test`, `npm run build`.
4. Re-run visual inspection.
5. Repeat until zero visual/interaction defects remain.

### 14.4 Completion Gate
Do not mark the project complete until:
- All automated QA gates pass.
- Local app runs successfully.
- Visual audit passes on all key pages.
- No console errors or broken UI elements remain.

