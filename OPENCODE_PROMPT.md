> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# OpenCode Prompt - Wedding Hall Platform Audit & Enhancement

## Project Overview
Laravel 11 + Livewire 3 + Tailwind CSS wedding venue marketplace & ERP platform (`f:\laravelproject\Wedding_hall`).

## Goals
1. Run the project on port 9000
2. Verify all pages (200 OK)
3. Check all button links
4. Fix all bugs
5. Add missing features for premium/professional status
6. Harden security
7. Optimize server load
8. Run full test suite (103 tests, 786 assertions)

## Execution Plan

### Phase 1: Environment & Server
- Start PHP server: `php artisan serve --port=9000 --host=127.0.0.1`
- Confirm `http://127.0.0.1:9000` returns 200 OK
- Verify database migration `2026_10_29_000001_create_hall_chat_messages_table.php` is applied

### Phase 2: Page Audit
- Homepage (`/`) - AI chatbot, navigation
- Venue list (`/venues`) - filtering, pagination
- Venue details (`/venues/{slug}`) - info, media, booking
- Hall chat (`/hall/{slug}/chat`) - per-hall isolation
- Public chat (`/public-chat`)
- Concierge (`/concierge`)
- Admin dashboard (`/admin`)
- User profiles & authentication
- Payroll/staff management
- Contact/FAQ pages

### Phase 3: Button & Link Verification
- All buttons (chat, submit, filter, download)
- All link targets
- WhatsApp buttons (per-hall)
- Livewire form submissions

### Phase 4: Bug Hunting & Fixing
- HallChat: remove `isLoading`, `chatOpen`, `updatedSelectedCustomerIp` hook
- HallChatMessage: ensure `User` import, Pint passes
- Routes: verify `/venues/{slug}/chat`, `/hall/{slug}/chat`, `/concierge`
- Middleware: CSRF, rate limiting, auth
- DB queries: replace `DB::raw`/`whereRaw` with Eloquent
- Upload endpoints: validate mime/size
- VenueChatWidget: proper integration
- Pagination: check all lists

### Phase 5: Security Hardening
- CSRF on all forms
- XSS prevention (Blade escaping)
- SQL injection prevention (Eloquent only)
- Auth & authorization
- Secure file uploads
- Rate limiting
- Session security

### Phase 6: Performance Optimization
- Database indexing
- Caching for static data
- Efficient Livewire updates
- Server keep-alive stability
- Background jobs
- Asset minification

### Phase 7: Testing & Quality
- Run full test suite (103 tests, 786 assertions)
- Manual verification of critical paths
- Pint style compliance (0 errors)
- Complete OPENCODE_PROMPT.md

## Required Sections

### C: Responsiveness Audit
- Mobile-first design
- All screen sizes (≤320px, 768px, 1024px, 1440px+)
- Touch-friendly buttons
- No horizontal scrolling
- Proper text scaling

### D: Bug Hunting
- List of bugs with severity (Critical, High, Medium, Low)
- Root cause analysis
- Fixes applied and verified
- Regression testing

### E: Code Quality
- Pint: 0 errors
- PSR-12 compliance
- Consistent naming
- Proper docblocks
- No unused code

### F: UI/UX Polish
- Consistent color/typography
- Smooth transitions
- Clear error messages
- Accessibility (WCAG 2.1 AA)
- Loading states

### G: Key Files
- `app/Models/HallChatMessage.php`
- `app/Livewire/VenueChatWidget.php`
- `routes/web.php`
- `database/migrations/2026_10_29_000001_create_hall_chat_messages_table.php`
- `config/platform.php`
- `tests/Feature/ComprehensiveDashboardCrudAuditTest.php`
- `tests/Feature/ConciergeChatbotTest.php`

### H: Testing Strategy
- Unit test coverage (103 tests, 786 assertions)
- Integration test coverage
- Load testing considerations
- Automated test script

### I: Important Notes
- Limitations and workarounds
- Future roadmap
- Maintenance considerations
- Backup/recovery procedures
- Deployment checklist

### J: Success Criteria
- All pages render (200 OK)
- No critical/high bugs
- Pint: 0 errors
- Test suite: 100% pass
- Security audit passed
- Performance meets load requirements
- Project 100% complete, ready for production on port 9000

## Prompt for OpenCode System

When you receive this prompt, OpenCode should:

1. Start the application: `php artisan serve --port=9000 --host=127.0.0.1`
2. Verify server runs on `http://127.0.0.1:9000` (200 OK)
3. Navigate every page and document:
   - What is displayed
   - Issues or missing sections
   - Button/link functionality
4. Run full test suite (103 tests, 786 assertions)
5. Perform comprehensive security review of routes, middleware, data access
6. Optimize performance (DB queries, caching, server load)
7. Add missing features to make platform premium/professional (100% complete)
8. Generate final report summarizing findings, fixes, and next steps

## Priority Order
1. Server startup & basic page verification
2. HallChat functionality (per-hall isolation)
3. Public chat & concierge features
4. Venue management & media
5. User authentication & roles
6. Security hardening
7. Performance optimization
8. Test suite execution
9. Documentation completion
10. Final validation & sign-off

## Success Metrics
- ✅ All 100+ pages accessible with 200 OK
- ✅ No critical/buggy functionality
- ✅ Pint: 0 errors
- ✅ Test suite: 100% pass (103 tests, 786 assertions)
- ✅ Security: All routes protected, no XSS/SQLi
- ✅ Performance: Stable under concurrent load
- ✅ Documentation: OPENCODE_PROMPT.md complete with all sections
- ✅ Project: 100% complete, ready for deployment on port 9000
