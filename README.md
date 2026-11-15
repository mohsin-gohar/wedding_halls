# Riwaj Grand (EventHub)

Riwaj Grand is a multi-tenant wedding & event marketplace and operations platform. It provides public discovery and booking for wedding halls **and** a complete multi-vendor ecosystem for photographers, caterers, decorators, beauty artists, planners and transport providers — with a hall ERP, a customer event workspace, vendor finance/commission tooling and a white-label vendor website option.

## Capabilities

- Public venue listings, galleries, packages, menus, availability, FAQs, and customer RSVP pages.
- **Multi-vendor marketplace** (`/vendors`, `/vendor/{slug}`, `/site/{slug}`) with DB-driven categories — a new business vertical needs no code change.
- **RFQ → quotation → award → service booking** engine with platform commission, vendor wallet ledger and payouts.
- **Customer event workspace** (`/customer/events`): multiple wedding functions, checklist, vendor shortlist, live budget.
- **Vendor ERP**: quotations & bids, service bookings, service catalogue, portfolio, multi-location branches (`/vendor/locations`), finance & earnings (`/vendor/finance`).
- **Support center** (`/account/support`) for customers, vendors and platform staff.
- Tenant-scoped booking calendar, walk-in reservations, customer records, payments, and printable documents.
- Staff, attendance, payroll, inventory, expenses, kitchen orders, floor plans, and venue media management.
- Subscription lifecycle and platform administration (21 roles · 110 granular permissions).
- **REST API** (`/api/v1`) with bearer-token auth for mobile/partner integrations.
- Optional Meta WhatsApp Cloud API integration for RSVP updates.

## Technology

- PHP 8.2+, Laravel 12, Livewire 4, and Spatie Laravel Permission.
- Vite 7, Tailwind CSS 4, and Alpine.js.
- SQLite for local development/tests; MySQL or PostgreSQL and Redis are recommended for production.
- Currency PKR, timezone `Asia/Karachi`.

## Local Setup

Requirements: PHP 8.2+, Composer, Node.js, and npm.

```powershell
composer run setup
php artisan db:seed
composer run dev
```

## Verification

```powershell
php artisan test
npm run build
composer audit
npm audit
```

## Project Layout

- `app/Livewire`: interactive application pages and workflows.
- `app/Services`: domain operations such as booking and subscription workflows.
- `app/Models` and `database/migrations`: persistence models and schema.
- `app/Http/Controllers` and `routes`: HTTP integrations, documents, and route definitions.
- `resources/views`, `resources/css`, and `resources/js`: Blade UI, design system, and browser code.
- `tests/Feature` and `tests/Unit`: automated verification.
- `deploy`: production environment, Nginx, queue-worker, and deployment guidance.
- `deploy/SCALING.md`: production scaling stages, shared-service requirements, query tuning, and load-test gates.

## Production

Start from `.env.example`; set a unique `APP_KEY`, keep `APP_ENV=production` and `APP_DEBUG=false`, and configure database, Redis, mail, storage, and HTTPS values for the target environment. WhatsApp webhook POSTs require `WHATSAPP_APP_SECRET` and a matching `X-Hub-Signature-256` header. See [deploy/DEPLOYMENT.md](deploy/DEPLOYMENT.md) for server prerequisites, deployment, TLS, workers, scheduling, and post-deploy checks.
