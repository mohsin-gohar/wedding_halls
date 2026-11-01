# EventHub Wedding Hall Platform — Comprehensive Audit & Fix Prompt

## Project Overview
Laravel 11 + Livewire 3 + Tailwind CSS wedding venue marketplace & ERP.
- Working Directory: f:\laravelproject\Wedding_hall
- Web Server: http://127.0.0.1:9000 (php artisan serve)
- Test Command: php vendor/bin/phpunit
- Lint Command: php vendor/bin/pint
- Migration Command: php artisan migrate

## Demo Credentials
| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@eventhub.com | admin1235 |
| Hall Owner | owner@venuecloud.pk | password |
| Front Desk | reception@venuecloud.pk | password |
| Chef | chef@venuecloud.pk | password |
| Customer | customer@example.com | password |

## Work Already Completed

### 1. Per-Venue WhatsApp Concierge
- Hall::getWhatsappUrl() and Hall::getWhatsappNumber() generate per-hall WhatsApp links
- WhatsApp button on public-hall-details.blade.php uses per-hall number

### 2. Per-Hall Chat System (Chatbot)
- Model: app/Models/HallChatMessage.php — IP + session tracking, per-hall isolation
- Component: app/Livewire/HallChat.php — Full-page chat with AI concierge bot
- Component: app/Livewire/VenueChatWidget.php — Floating chat widget on venue pages
- Component: app/Livewire/PublicChatWidget.php — Platform-wide chatbot in public layout
- Routes: /concierge, /venues/{slug}/chat, /hall/{slug}/chat
- Migration: 2026_10_29_000001_create_hall_chat_messages_table.php
- AI Bot: Context-aware responses (rent, catering, facilities, greetings, etc.)
- Voice: Audio message upload support (MP3/WAV/OGG/M4A/WEBM, max 5MB)

### 3. Hall Admin ERP Dashboard
- Expenses, Walk-in booking, Staff management, Attendance, Payroll
- Inventory tracking, Catering packages, Hall media & services
- Floor plan designer, WhatsApp RSVP manager
- Live guest chat (read + reply from dashboard)

### 4. Super Admin Dashboard
- Platform tasks (CRUD), Vendor KYC (approve/suspend)
- Traffic switches (registration, marketplace, announcement)
- SaaS plans CRUD (when billing enabled)
- Subscription payment clearance
- Contact inquiry inbox

### 5. Public Pages
- Homepage with hero search (date, shift, guests)
- Venues directory with filters (city, search, capacity, availability)
- Venue details (hero, pricing, gallery, calendar, reviews, FAQ, chat)
- Availability checker, Contact, About, FAQ, Policies, Gallery, Packages, Services

## Known Issues Already Fixed
1. HallChatMessage model — added User import
2. HallChat — removed unused isLoading and chatOpen properties
3. HallChat — removed redundant updatedSelectedCustomerIp hook
4. HallMediaManager — changed venue_name validation from required to nullable
5. StaffManager/Attendance/Payroll — switched to User::hallStaff() scope
6. PublicVenues — added date/shift/guest filters with availability checking
7. EnsurePortalRole — centralized redirect via User::portalHomeRoute()

## Remaining Tasks for opencode

### A. Security Audit
- Verify all Livewire components have proper auth guards (mount checks)
- Check CSRF protection on all forms
- Verify file upload validation (mimes, max size) on all upload endpoints
- Check for SQL injection in raw queries (search for DB::raw, whereRaw)
- Verify XSS protection (ensure {{ }} not {!! !!} for user content)
- Check tenant isolation (ensure all queries filter by tenant_id)
- Verify rate limiting on public endpoints
- Check for IDOR vulnerabilities
- Review middleware chain (auth, tenant.staff, portal.role, super.admin)

### B. Performance Audit
- Check N+1 queries in all Livewire render() methods
- Verify eager loading (with() calls) on all model queries
- Check pagination on large datasets
- Verify caching on expensive queries
- Check image optimization (lazy loading, responsive sizes)
- Verify database indexes on foreign keys
- Check for unnecessary DB queries in loops

### C. Responsiveness Audit
- Test all pages on mobile (320px, 375px, 414px)
- Test all pages on tablet (768px, 1024px)
- Test all pages on desktop (1280px, 1440px, 1920px)
- Verify navigation works on mobile (hamburger menu)
- Check form inputs usable on mobile (touch targets)
- Verify modals/dialogs scrollable on small screens
- Check calendar grid responsiveness
- Verify chat widget doesn't block content on mobile

### D. Bug Hunting
- Test all CRUD operations end-to-end for every module
- Test edge cases (empty states, invalid inputs, boundary values)
- Test concurrent booking scenarios
- Test file uploads (images, videos, voice notes, documents)
- Test pagination and filtering
- Test calendar navigation (prev/next month, year jump)
- Test chat: send message, receive bot reply, voice upload, hall owner reply
- Test WhatsApp RSVP simulation
- Test walk-in booking flow (all steps)
- Test staff payroll generation
- Test inventory stock adjustments
- Test floor plan save/load
- Test review submission and approval
- Test wishlist add/remove
- Test dark/light theme toggle

### E. Code Quality
- Run php vendor/bin/pint and fix all style issues
- Run php vendor/bin/phpunit and ensure all tests pass
- Check for unused imports, variables, methods
- Verify all Livewire components have proper validation rules
- Check for consistent error handling patterns
- Verify all database migrations are up to date
- Check for missing foreign key constraints
- Verify all models have proper fillable/casts

### F. UI/UX Polish
- Consistent color scheme (gold #C6A15B, dark #171513)
- Consistent typography (Cinzel, Cormorant Garamond, Plus Jakarta Sans)
- Loading states on all async actions (wire:loading)
- Error messages displayed properly (validation errors)
- Success feedback (flash messages, toasts)
- Empty states for all lists
- Breadcrumbs on all pages
- Page titles and meta descriptions

## Key Files to Review
Models: Hall.php, HallChatMessage.php, Booking.php, Tenant.php, User.php
Livewire: HallChat.php, VenueChatWidget.php, PublicChatWidget.php, PublicHallDetails.php, PublicVenues.php, Dashboard.php, AdminDashboard.php, HallMediaManager.php, WalkInBookingModal.php, StaffManager.php, StaffAttendance.php, StaffPayroll.php, InventoryTracker.php, PackageManager.php, FloorPlanDesigner.php, WhatsAppRsvpManager.php, CalendarShiftGrid.php
Services: BookingService.php, KitchenOrderService.php, SubscriptionService.php, PlatformAnalyticsService.php
Middleware: EnsurePortalRole.php, EnsureTenantStaff.php, EnsureSuperAdmin.php, EnsureActiveAccount.php
Routes: routes/web.php
Views: public-hall-details.blade.php, public-venues.blade.php, hall-chat.blade.php, venue-chat-widget.blade.php, dashboard.blade.php, admin-dashboard.blade.php, public.blade.php, app.blade.php

## Testing Strategy
1. Run php vendor/bin/phpunit — must pass all tests
2. Run php vendor/bin/pint — must pass all style checks
3. Manually test each page in browser at http://127.0.0.1:9000
4. Test on mobile viewport (Chrome DevTools)
5. Test all CRUD operations for each module
6. Test edge cases and error states

## Important Notes
- BILLING_ENABLED=false in .env — SaaS billing disabled during free launch
- KITCHEN_MODULE_ENABLED=false in .env — Kitchen module disabled
- Database uses SQLite for testing (RefreshDatabase trait)
- All tests use RefreshDatabase + seed()
- Web server runs on port 9000
- Assets built with Vite (npm run build)

## Success Criteria
- All tests pass (103+ tests, 786+ assertions)
- Pint passes with 0 issues
- No PHP errors in storage/logs/laravel.log
- All pages render correctly on desktop and mobile
- All CRUD operations work end-to-end
- No security vulnerabilities (SQL injection, XSS, CSRF, IDOR)
- No N+1 query issues
- Responsive on all screen sizes
- Dark/light theme works on all pages
- Chat widget works (send, receive, voice, reply)
- WhatsApp per-hall numbers display correctly
- Calendar navigation works
- File uploads work (images, videos, voice notes)
- Booking flow works end-to-end
- Staff management works (create, edit, attendance, payroll)
- Inventory management works
- Package management works
- RSVP management works
- Floor plan designer works
- Review system works
- Wishlist works
- Contact form works
- Admin dashboard works
- Hall admin dashboard works
- Customer dashboard works
- Public pages work
- Auth works (login, register, logout, role-based redirect)
- Middleware works
- Rate limiting works
- CSRF protection on all forms
- Tenant isolation verified
- No XSS/SQL injection/IDOR
- Mobile responsive
- All tests pass
- Pint passes
- No errors in logs