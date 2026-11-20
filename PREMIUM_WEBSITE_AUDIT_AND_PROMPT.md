> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# EventHub — Premium $20,000 Website: Full Audit, Bug Report & Master Build Prompt

> **Project:** Wedding_hall (EventHub) — Laravel 11 + Livewire 3 + Tailwind v4 + Alpine.js
> **Goal:** Transform into a $20,000-grade, production-ready, premium hospitality SaaS marketplace.
> **Prepared for:** AI coding agent / senior full-stack developer
> **Date:** 2026-10-23

---

## 0. Executive Summary

EventHub is already a feature-rich multi-tenant wedding-venue SaaS (public marketplace, tenant ERP, customer portal, super-admin console, WhatsApp RSVP, KOT, inventory, payroll). Foundation is solid: 68/68 PHPUnit tests pass, Vite build passes, security middleware (CSP, HSTS, security headers) in place, luxury gold/obsidian design system.

To reach a **$20,000 premium-grade** product, address:
1. **Bugs & gaps** in Part A (storage link, `.env.example` defaulting to SQLite, missing production defaults).
2. **Premium features** in Part B (payments, analytics, i18n, PWA, real-time, accessibility, SEO, legal).
3. **QA gates** in Part D must all pass before launch.

---

## Part A — Audit Findings: Bugs, Errors & Gaps

### A.1 Confirmed Bugs (must fix)

| # | Severity | Location | Issue | Fix |
|---|----------|----------|-------|-----|
| 1 | High | `.env.example` line 23 | Defaults to `DB_CONNECTION=sqlite`. Production should default to MySQL. | Set `DB_CONNECTION=mysql` in `.env.example`, uncomment MySQL block. |
| 2 | High | `public/storage` (Junction) | Storage symlink may be stale on fresh clones; uploaded media 404. | Run `php artisan storage:link` in deploy; add to composer post-install. |
| 3 | Medium | `resources/views/errors/404.blade.php`, `500.blade.php` | Verify auto-resolution in Laravel 11. | Test by hitting `/nonexistent`. |
| 4 | Medium | `config/app.php` | `force_https` defaults false. | Default `FORCE_HTTPS=true` in production. |
| 5 | Low | `resources/views/welcome.blade.php` hero | Slide image from `i.pinimg.com` (Pinterest) hotlink. | Self-host or use Unsplash/Pexels CDN. |
| 6 | Low | blade files | Verify UTF-8 encoding without BOM. | Re-save all blade files. |
| 7 | Low | `app/Http/Middleware/SecurityHeaders.php` | CSP allows `'unsafe-eval'` (needed by Alpine). | Keep; nonce-based CSP in v2. |

### A.2 Performance Gaps
1. Home closure (`routes/web.php`): `Hall::with(['venue','tenant'])` + `Package::where(...)` no caching. → Cache 5 min.
2. Public pages: no HTTP cache headers/ETag. → Add `Cache-Control: public, max-age=60`.
3. Images: no lazy loading, srcset, WebP. → Add lazy + responsive + WebP pipeline.
4. DB: no indexes on `halls.slug`, `tenants.verification_status`, `bookings.event_date`. → Add migration.
5. N+1 risk in `PublicMarketplace`, `PublicVenues`, `PublicHallDetails`. → Audit eager loading.

### A.3 SEO / Marketing Gaps
1. No sitemap.xml / robots.txt → add `spatie/laravel-sitemap`.
2. No OG/Twitter meta → add to public layout.
3. No JSON-LD (EventVenue) → add to hall detail.
4. No canonical URLs → add `<link rel="canonical">`.
5. No blog → add `/blog`.

### A.4 Accessibility Gaps
1. Icon buttons lack `aria-label`.
2. Gold `#C6A15B` on white may fail WCAG AA contrast.
3. No skip-to-content link.
4. Some form inputs lack `<label>`.
5. No focus-visible styles.
6. Modals lack `role="dialog"`, `aria-modal`.

### A.5 Missing Production Features
See Part B.

---
## Part B — $20,000 Premium Website Blueprint

A $20k professional hospitality SaaS must include ALL of the following.

### B.1 Public Marketing Site (conversion-focused)
- [ ] Cinematic hero with video background + parallax
- [ ] Real-time availability search (date, city, capacity, budget)
- [ ] Featured venues carousel with reviews & ratings
- [ ] "How it works" 3-step visual flow
- [ ] Testimonials (real customer quotes with photos)
- [ ] Trust badges (SSL, verified venues, money-back)
- [ ] Newsletter signup (Mailchimp/ConvertKit)
- [ ] Live chat widget (Tawk/Intercom/Crisp)
- [ ] WhatsApp floating button
- [ ] Cookie consent banner (GDPR/PDPA)
- [ ] Multi-language (English + Urdu) with RTL
- [ ] Currency selector (PKR, USD, GBP, AED)
- [ ] Blog with SEO articles
- [ ] FAQ with search
- [ ] Contact form with Google Maps embed
- [ ] Careers page
- [ ] Press/media kit

### B.2 Venue Discovery & Booking
- [ ] Advanced filters: date, city, capacity, price, amenities
- [ ] Map view (Google Maps/Mapbox) with pins
- [ ] Venue detail: gallery (lightbox), 360 tour, floor plan, amenities, reviews, availability calendar
- [ ] Real-time availability calendar (morning/evening/full-day shifts)
- [ ] Instant quote calculator
- [ ] Online booking with deposit (Stripe/JazzCash/EasyPaisa/bank)
- [ ] Booking confirmation email + SMS + WhatsApp
- [ ] Digital contract e-signature
- [ ] Payment schedule (deposit, installments, final)
- [ ] Refund/cancellation policy enforcement
- [ ] Waitlist for fully-booked dates
- [ ] "Similar venues" recommendations
- [ ] Save to wishlist (shareable link)
- [ ] Compare venues side-by-side (up to 3)

### B.3 Customer Portal
- [ ] Dashboard: upcoming bookings, payment status, documents
- [ ] Booking management: reschedule, cancel, add services
- [ ] Payment history & downloadable receipts
- [ ] Guest list management (RSVP tracking)
- [ ] Event timeline / checklist
- [ ] Chat with venue coordinator
- [ ] Review & rating after event
- [ ] Photo gallery upload (post-event)
- [ ] Referral program (earn credit)
- [ ] Loyalty points / rewards

### B.4 Vendor (Tenant) ERP
- [ ] Dashboard with KPIs (revenue, bookings, occupancy, avg rating)
- [ ] Calendar: drag-and-drop shift management, conflict detection
- [ ] Booking pipeline (inquiry → confirmed → completed)
- [ ] Customer CRM (history, preferences, notes)
- [ ] Quotation builder (PDF, email)
- [ ] Invoice & receipt (tax-compliant)
- [ ] Payment tracking (partial, overdue alerts)
- [ ] Catering menu management
- [ ] Inventory & stock (low-stock alerts)
- [ ] Staff management (roles, shifts, attendance, payroll)
- [ ] Utility & fuel tracking
- [ ] Kitchen KOT (order tickets, status)
- [ ] Floor plan designer (drag-and-drop seating)
- [ ] WhatsApp RSVP manager (automated reminders)
- [ ] Expense tracker with categories & reports
- [ ] Document vault (contracts, licenses, insurance)
- [ ] Analytics: revenue trends, popular dates, demographics
### B.5 Super-Admin Console
- [ ] Tenant onboarding & verification (KYC, documents)
- [ ] Subscription plan management (pricing, features, trials)
- [ ] Billing & invoicing (automated, dunning)
- [ ] Platform analytics (GMV, active tenants, churn, MRR/ARR)
- [ ] Content moderation (reviews, listings)
- [ ] Feature flags & A/B testing
- [ ] Email/SMS templates management
- [ ] Audit log (who did what, when)
- [ ] System health monitoring (uptime, queue, errors)
- [ ] Impersonate tenant (support)
- [ ] Announcements / broadcast messages

### B.6 Integrations
- [ ] Payments: Stripe, JazzCash, EasyPaisa, bank transfer, cash
- [ ] SMS: Twilio / local (Jazz, Telenor)
- [ ] Email: SendGrid / Mailgun / SES
- [ ] WhatsApp Business API (Meta Cloud API) — already wired
- [ ] Google Maps (location, directions)
- [ ] Google Calendar sync (bookings)
- [ ] Accounting: QuickBooks / Xero export
- [ ] Social login (Google, Facebook, Apple)
- [ ] Analytics: GA4, Meta Pixel, Hotjar
- [ ] Error tracking: Sentry
- [ ] CDN: Cloudflare (assets, images)

### B.7 Technical Excellence (production-grade)
- [ ] Queue workers (Redis) for emails/SMS/WhatsApp
- [ ] Scheduled tasks (expiry, reminders, reports)
- [ ] DB backups (daily, offsite)
- [ ] Rate limiting on all public endpoints
- [ ] CSRF, XSS, SQLi protection (Laravel defaults + audit)
- [ ] 2FA for admin & vendor
- [ ] RBAC (already present)
- [ ] API rate limiting + throttling
- [ ] Webhook signature verification (WhatsApp)
- [ ] GDPR/PDPA data export & deletion
- [ ] Cookie consent + privacy policy (present)
- [ ] Accessibility WCAG 2.1 AA
- [ ] Lighthouse ≥ 90 (perf, a11y, BP, SEO)
- [ ] Core Web Vitals: LCP<2.5s, CLS<0.1, INP<200ms
- [ ] PWA: installable, offline fallback, push
- [ ] Responsive: mobile-first, iOS/Android tested
- [ ] Dark mode (present)
- [ ] i18n: English + Urdu (RTL)
- [ ] Multi-tenant isolation (present, audit thoroughly)
- [ ] Soft deletes + audit trail on critical models
- [ ] DB transactions on booking/payment flows
- [ ] Idempotency keys on payment webhooks

## Part C — Master Build Prompt (copy-paste for AI agent / developer)

You are a senior full-stack engineer building EventHub, a $20,000-grade premium wedding-venue SaaS marketplace. Stack: Laravel 11, Livewire 3, Tailwind v4, Alpine.js, MySQL (prod)/SQLite (dev), Redis queues.

MISSION: Transform the existing EventHub codebase into a production-ready, premium, $20,000-quality hospitality platform. Fix every bug in Part A. Implement every feature in Part B. Meet every QA gate in Part D.

RULES:
1. Never break existing tests (68 must stay green; add new tests for new features).
2. Run `php artisan test` and `npm run build` after every change; both must pass.
3. Run Laravel Pint (`vendor/bin/pint`) on all PHP changes.
4. Keep the luxury gold/obsidian design language; do not introduce a new theme.
5. Every public page must return 200; every protected route must redirect guests to login.
6. All user-facing strings must be translatable (use __('...')).
7. All money in PKR with proper formatting (e.g. PKR 150,000).
8. Mobile-first responsive; test at 375px, 768px, 1280px.
9. Accessibility: WCAG 2.1 AA minimum (contrast, labels, focus, aria).
10. Performance: Lighthouse ≥ 90 on home, venue detail, dashboard.

PHASE 1 — BUG FIXES (Part A.1):
- Fix .env.example default DB to MySQL; document SQLite for dev.
- Ensure storage:link in deploy; verify uploaded media serves.
- Verify custom error pages render (404/500).
- Move hero image from Pinterest to self-hosted/Unsplash CDN.
- Verify UTF-8 encoding of all blade files.
- Add DB indexes migration (halls.slug, tenants.verification_status, bookings.event_date).

PHASE 2 — PERFORMANCE & SEO (Part A.2, A.3):
- Cache featured halls/packages on home (5 min).
- Add lazy loading + WebP for images.
- Generate sitemap.xml + robots.txt.
- Add OG/Twitter meta + JSON-LD on public layout & hall detail.
- Add canonical URLs.

PHASE 3 — ACCESSIBILITY (Part A.4):
- Add skip-to-content link, aria-labels, focus-visible, form labels.
- Verify color contrast; adjust gold if needed.

PHASE 4 — PREMIUM FEATURES (Part B) in order of revenue impact:
  1. Online payments (JazzCash/EasyPaisa/Stripe) + deposit flow
  2. Real-time availability calendar with shift selection
  3. Instant quote calculator
  4. Digital contract + e-signature
  5. Customer portal (bookings, payments, documents)
  6. Vendor analytics dashboard (revenue, occupancy, trends)
  7. Reviews & ratings
  8. Map view (Google Maps)
  9. WhatsApp automated reminders
  10. PWA (manifest, service worker, offline)
  11. i18n (English + Urdu RTL)
  12. Blog + SEO content
## Part D — Acceptance Criteria / QA Checklist

### D.1 Functional
- [ ] All public routes return 200 (no 500s).
- [ ] All protected routes redirect guests → login.
- [ ] Role-based access enforced (customer cannot see vendor pages).
- [ ] Booking flow end-to-end (search → detail → book → pay → confirm → email).
- [ ] Payment webhook handles success/failure/idempotency.
- [ ] Cancellation & refund flow works per policy.
- [ ] Tenant isolation: no cross-tenant data leakage (verified by tests).
- [ ] Super-admin can verify/suspend tenants.

### D.2 Non-functional
- [ ] `php artisan test` → 0 failures.
- [ ] `npm run build` → success.
- [ ] `vendor/bin/pint --test` → no style violations.
- [ ] Lighthouse (mobile): Performance ≥ 90, Accessibility ≥ 90, Best Practices ≥ 90, SEO ≥ 90.
- [ ] Core Web Vitals: LCP < 2.5s, CLS < 0.1, INP < 200ms.
- [ ] axe-core: 0 critical a11y violations.
- [ ] No console errors on any page.
- [ ] No broken images / 404 assets.
- [ ] All forms validate with helpful messages.
- [ ] All destructive actions have confirmation dialogs.

### D.3 Security
- [ ] HTTPS enforced in production (FORCE_HTTPS=true).
- [ ] Security headers present (CSP, HSTS, X-Frame-Options, etc.).
- [ ] CSRF on all forms.
- [ ] Rate limiting on auth, contact, RSVP, webhooks.
- [ ] 2FA available for admin + vendor.
- [ ] No secrets in repo (.env gitignored).
- [ ] SQL injection / XSS audit clean.

### D.4 Operations
- [ ] Automated DB backups (daily, offsite).
- [ ] Queue worker + scheduler running (supervisor/systemd).
- [ ] Error tracking (Sentry) configured.
- [ ] Uptime monitoring (UptimeRobot / BetterStack).
- [ ] Runbook for common incidents documented.
- [ ] Rollback procedure documented.

---

## Appendix — File Index (key files)

| Area | Files |
|------|-------|
| Routes | `routes/web.php`, `routes/console.php` |
| Public pages | `app/Livewire/Public*`, `resources/views/livewire/public-*.blade.php` |
| Vendor ERP | `app/Livewire/Dashboard.php`, `CalendarShiftGrid.php`, `KitchenKotBoard.php`, `StaffManager.php`, `StaffPayroll.php`, `InventoryTracker.php`, `PackageManager.php`, `ExpenseTracker.php`, `FloorPlanDesigner.php`, `UtilityFuelTracker.php`, `WhatsAppRsvpManager.php`, `HallMediaManager.php` |
| Customer | `app/Livewire/CustomerDashboard.php`, `CustomerLogin.php`, `CustomerRegister.php` |
| Admin | `app/Livewire/AdminDashboard.php` |
| Auth | `app/Livewire/Auth/Login.php`, `Register.php` |
| Models | `app/Models/*` (25 models) |
| Middleware | `app/Http/Middleware/*` |
| Layouts | `resources/views/components/layouts/*` |
| Error pages | `resources/views/errors/404.blade.php`, `500.blade.php` |
| Design system | `resources/css/app.css` |
| Tests | `tests/Feature/*` (16 files, 68 tests) |
| Config | `config/*.php`, `.env.example` |

---

*This document is the single source of truth for the EventHub premium upgrade. Hand it to your AI agent or development team and execute Phase 1 → 5 in order.*

  13. Live chat widget
  14. 2FA for admin/vendor
  15. Sentry error tracking + GA4 analytics

PHASE 5 — QA & LAUNCH (Part D):
Run full checklist; fix until all green. Deploy behind Nginx + Cloudflare with TLS.

DELIVERABLES:
- All tests green (target: 120+ tests).
- Lighthouse ≥ 90 on 5 key pages.
- WCAG 2.1 AA audit (axe-core) passes.
- Load test: 100 concurrent users on home + venue detail, p95 < 1s.
- Security scan (OWASP ZAP) no high/critical findings.
- Production .env checklist documented.

---

### B.8 Design & Brand (premium feel)
- [ ] Custom brand identity (logo, palette, typography)
- [ ] Consistent design tokens (CSS vars) — present
- [ ] Motion design (GSAP/Framer micro-interactions)
- [ ] Skeleton loaders (no blank screens)
- [ ] Empty states with illustrations
- [ ] Error states with helpful CTAs
- [ ] Toast notifications (present via luxury alert dialog)
- [ ] Confirmation dialogs for destructive actions
- [ ] Progress indicators for multi-step flows
- [ ] Print-optimized documents (present)

---

- [ ] Export (PDF, Excel, CSV)
- [ ] Mobile-responsive PWA for on-site staff

---

