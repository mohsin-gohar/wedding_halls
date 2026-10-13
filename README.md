# VenueCloud

VenueCloud is a multi-tenant wedding venue marketplace and operations platform. It provides public venue discovery and booking inquiries alongside tools for venue owners to manage reservations, staff, inventory, finances, catering, and event operations.

## Capabilities

- Public venue listings, galleries, packages, menus, availability, FAQs, and customer RSVP pages.
- Tenant-scoped booking calendar, walk-in reservations, customer records, payments, and printable documents.
- Staff, attendance, payroll, inventory, expenses, kitchen orders, floor plans, and venue media management.
- Subscription lifecycle and platform administration.
- Optional Meta WhatsApp Cloud API integration for RSVP updates.

## Technology

- PHP 8.2+, Laravel 12, Livewire 4, and Spatie Laravel Permission.
- Vite 7, Tailwind CSS 4, and Alpine.js.
- SQLite for local development/tests; MySQL or PostgreSQL and Redis are recommended for production.

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

## Production

Start from `.env.example`; set a unique `APP_KEY`, keep `APP_ENV=production` and `APP_DEBUG=false`, and configure database, Redis, mail, storage, and HTTPS values for the target environment. WhatsApp webhook POSTs require `WHATSAPP_APP_SECRET` and a matching `X-Hub-Signature-256` header. See [deploy/DEPLOYMENT.md](deploy/DEPLOYMENT.md) for server prerequisites, deployment, TLS, workers, scheduling, and post-deploy checks.
