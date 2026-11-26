> [!WARNING]
> **Historical / superseded document.** This file predates the current cash-only audit and verification pass. Do not rely on its claims about payment methods, test counts, browser proof, or production readiness. See `DOCS_STATUS.md` and the authoritative final reports.

# RIWAJ GRAND — RBAC PERMISSION MATRIX

> Source of truth: `app/Support/PermissionMatrix.php`
> Seeder: `database/seeders/PermissionMatrixSeeder.php` (idempotent — re-runnable, syncs without detaching users)
> Live state: **110 permissions · 21 roles** (was ~57 permissions · 9 roles)

---

## 1. Model

- **Permission naming:** `<group>.<action>`, Spatie guard `web`.
- **Roles** are stored on `users.role` (legacy string, preserved) **and** mirrored into Spatie roles so granular permissions work.
- **Enforcement is server-side only.** Route middleware (`portal.role:*`), policies (`app/Policies/*`) and per-component `abort(403)` guards. The Blade `can()` calls are cosmetic.
- **New role names are aliases** of the legacy vocabulary via `PermissionMatrix::ROLE_ALIASES`, so every existing `isManager()` / `hasRole()` check keeps working while the platform speaks enterprise role language.
- **Least privilege is enforced by construction**: no role holds both financial authority and security authority.

---

## 2. Permission groups (35 groups)

| Group | Actions |
|---|---|
| `vendors` | view, verify, suspend, feature, categories.manage, documents.review |
| `listings` | view, moderate, publish |
| `reviews` | view, moderate, reply, manage |
| `audit` | view |
| `settings` | manage |
| `roles` | manage |
| `subscription` | manage |
| `customers` | view |
| `support` | view, manage, assign |
| `disputes` | view, create, manage |
| `messages` | view, send, manage |
| `leads` | view, manage |
| `coupons` | view, manage |
| `reports` | view, financial, export |
| `dashboard` | view |
| `business` | view, update |
| `locations` | view, manage |
| `services` | view, create, update, delete, manage |
| `packages` | view, manage |
| `portfolio` | view, manage |
| `availability` | view, manage |
| `documents` | view, manage |
| `staff` | view, manage, attendance |
| `inventory` | view, manage |
| `kitchen` | view, manage |
| `events` | view, create, update, delete |
| `bookings` | view, create, update, cancel, manage |
| `service-bookings` | view, create, update, cancel, confirm, complete |
| `rfq` | view, create, bid, award, close |
| `quotations` | view, create, approve, revise |
| `contracts` | view, create, manage, approve |
| `events-planning` | view, manage |
| `payments` | view, record, verify, refund, manage |
| `invoices` | view, manage |
| `payouts` | view, request, approve, manage |
| `wallet` | view |
| `commissions` | view, manage |
| `reconciliation` | view, manage |
| `expenses` | view, manage |
| `payroll` | view, manage |
| `api` | use |

---

## 3. Platform roles

### ROLE_SUPER_ADMIN — `*` (all 110)
Full platform governance: vendors, categories, plans, commissions, payouts, disputes, reviews, support, analytics, audit, roles.

### ROLE_PLATFORM_SUPPORT — support operations only
```
support.view, support.manage, support.assign, customers.view, vendors.view,
vendors.documents.review, bookings.view, service-bookings.view, rfq.view,
disputes.view, disputes.create, disputes.manage, messages.view, messages.send,
reviews.view, reports.view
```
❌ **Cannot** approve payouts, manage commissions, manage roles or change platform settings.

### ROLE_PLATFORM_FINANCE — money only
```
payments.view/record/verify/refund, invoices.view/manage, payouts.view/approve/manage,
wallet.view, commissions.view/manage, reconciliation.view/manage, reports.view/financial/export,
subscription.manage, vendors.view, audit.view
```
❌ **Cannot** manage staff, roles or platform settings.

### ROLE_CONTENT_MODERATOR — listings & reviews only
```
listings.view/moderate/publish, reviews.view/moderate/reply, portfolio.view,
vendors.view, vendors.documents.review, messages.view
```
❌ **Cannot** read any financial record, and cannot change vendor ownership.

---

## 4. Vendor roles (always tenant-scoped)

| Role | Permissions |
|---|---|
| **ROLE_TENANT_OWNER** / **ROLE_VENDOR_OWNER** (alias) | Everything inside the business: dashboard, business, locations, bookings, service-bookings, RFQ (bid/award/close), quotations, contracts, payments (view/record/verify), invoices, payouts.request, wallet, commissions.view, reconciliation.view, expenses, payroll, staff, inventory, kitchen, packages, services, portfolio, availability, documents, events, planning, leads, messages, reviews.reply, reports, customers, subscription, settings |
| **ROLE_MANAGER** / **ROLE_VENDOR_MANAGER** (alias) | Operational: bookings (view/update/cancel/manage), service-bookings, RFQ bid/award, quotations, staff.view/attendance, kitchen/inventory/packages/services (read), documents/locations (read), events, leads, messages, reports.view |
| **ROLE_ACCOUNTANT** / **ROLE_VENDOR_ACCOUNTANT** (alias) | Financial: payments (view/record), invoices, payouts.request, wallet, commissions.view, reconciliation.view, expenses, payroll, reports.financial/export |
| **ROLE_FRONT_DESK** / **ROLE_BOOKING_MANAGER** (alias) | Booking desk: bookings (view/create/update), service-bookings, rfq view/create/bid, quotations.create, customers.view, payments (view/record), messages, availability |
| **ROLE_CHEF** / **ROLE_CATERING_STAFF** (alias) | kitchen.view/manage, inventory.view, bookings.view, service-bookings.view |
| **ROLE_FLOOR_STAFF** | bookings.view, bookings.create, kitchen.view, service-bookings.view |
| **ROLE_SERVICE_STAFF** | dashboard.view, service-bookings.view, rfq.view, availability.view, portfolio.view |
| **ROLE_STAFF_MEMBER** | dashboard.view, service-bookings.view, bookings.view, events.view, availability.view, portfolio.view |
| **ROLE_PHOTOGRAPHER** | dashboard.view, service-bookings.view, bookings.view, portfolio.view/manage, availability.view, events.view |
| **ROLE_INVENTORY_MANAGER** | inventory.view/manage, packages.view, services.view, reports.view |
| **ROLE_HR_MANAGER** | staff.view/manage/attendance, payroll.view/manage, reports.view |

## 5. Customer role

### ROLE_CUSTOMER — no platform permissions
Customers are authorised by **ownership**, not by permission bits: a customer may read a booking only when `booking.customer_id === user.id`, an event only when `event.user_id === user.id`, a ticket only when `ticket.user_id === user.id`.

---

## 6. Role families (aliases)

```php
ROLE_ALIASES = [
    'ROLE_VENDOR_OWNER'     => 'ROLE_TENANT_OWNER',
    'ROLE_VENDOR_MANAGER'   => 'ROLE_MANAGER',
    'ROLE_VENDOR_ACCOUNTANT'=> 'ROLE_ACCOUNTANT',
    'ROLE_BOOKING_MANAGER'  => 'ROLE_FRONT_DESK',
    'ROLE_CATERING_STAFF'   => 'ROLE_CHEF',
    'ROLE_HR_MANAGER'       => 'ROLE_MANAGER',
];
```

`User::isRoleFamily('ROLE_TENANT_OWNER')` returns true for both `ROLE_TENANT_OWNER` and `ROLE_VENDOR_OWNER`, so every legacy guard keeps its meaning.

---

## 7. Assignment safety

| Rule | Enforcement |
|---|---|
| Public registration may only create | `ROLE_TENANT_OWNER` / `ROLE_VENDOR_OWNER` (`PermissionMatrix::publicRegistrationRoles()`); the tenant starts `verification_status = PENDING` |
| Platform roles are never assignable by a tenant | `PermissionMatrix::assignableVendorRoles()` excludes `ROLE_SUPER_ADMIN`, `ROLE_PLATFORM_SUPPORT`, `ROLE_PLATFORM_FINANCE`, `ROLE_CONTENT_MODERATOR`, `ROLE_CUSTOMER`; enforced in `StaffManager::allowedRoles()` **and** the validation rule |
| Staff can only be created inside the caller's tenant | `StaffManager` scopes by `tenant_id`; `EnsureTenantStaff` blocks customers and unassigned accounts |
| A role never implies access to another tenant's rows | Every query is additionally filtered by `tenant_id`; `ServiceBookingPolicy`, `VendorPolicy`, `VendorPayoutPolicy` and `DocumentController` enforce ownership independently of permissions |
| Role changes are auditable | `audit_logs` table with `action`, `entity_type`, `entity_id`, `user_id`, `ip_address` |

---

## 8. Regression tests

`tests/Feature/EnterpriseExpansionTest.php` asserts:
- all 14 enterprise roles exist and >100 permissions are defined
- platform support **cannot** reach `payouts.approve` / `commissions.manage` / `roles.manage` / `settings.manage`
- platform finance **cannot** reach `roles.manage` / `staff.manage` / `settings.manage`
- content moderator **cannot** read any financial record
- `ROLE_VENDOR_OWNER` inherits the tenant-owner capability set
- platform roles are excluded from staff assignment
- public registration yields `ROLE_TENANT_OWNER` and a `PENDING` tenant
- API cross-tenant and cross-customer access returns nothing
