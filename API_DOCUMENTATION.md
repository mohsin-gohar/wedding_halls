# RIWAJ GRAND — API DOCUMENTATION (v1)

> Base path: `/api/v1` · Defined in `routes/api.php` · Registered by `bootstrap/app.php`
> Authentication: **bearer token** (mobile / partner) **or** browser session (ERP & partner dashboards)
> Content type: `application/json` · Currency: PKR · Timezone: `Asia/Karachi`

---

## 1. Authentication

### 1.1 Issuing a token
From a signed-in web session:

```http
POST /account/api-tokens
Cookie: riwayaj_session=…

{ "name": "Android phone", "expires_in_days": 90 }
```

```json
{
  "message": "API token created. Copy it now — it will not be shown again.",
  "token": "9f2c…64-char secret",
  "id": 42,
  "prefix": "9f2c1a77",
  "expires_at": "2026-02-11T12:00:00+05:00"
}
```

The plain token is returned **exactly once**. Only its SHA-256 hash is stored (`api_tokens.token_hash`, unique). The secret is shown in clear in the response only.

### 1.2 Using a token
```http
GET /api/v1/me
Authorization: Bearer 9f2c1a77…
```

The `api` guard resolves the token, rejects it when it is **revoked, expired, or belongs to a non-active account**, and records `last_used_at` / `last_used_ip`.

### 1.3 Revoking
```http
DELETE /account/api-tokens/{id}
```
The token immediately returns **401**.

### 1.4 Failure modes
| Condition | Response |
|---|---|
| Missing / malformed / revoked / expired token **and** no session | `401 {"message":"Unauthenticated. A valid bearer token is required."}` |
| Token belongs to a suspended/inactive/blocked account | `403 {"message":"This account is not active."}` |
| Authenticated but lacking the required permission | `403 {"message":"Missing required permission: …"}` |
| Authenticated but belonging to a different tenant | `403 {"message":"Cross-tenant access denied."}` |
| Rate limit exceeded | `429` with `Retry-After` |

---

## 2. Authorization rules (applied on every endpoint)

| Principal | Scope |
|---|---|
| Super Admin | Everything |
| Platform Finance / Content Moderator | Everything read-only on their domain |
| Tenant staff | `tenant_id = user.tenant_id` — **always** |
| Customer | `customer_id = user.id` — **always** |

There is no endpoint that returns an unscoped result set.

---

## 3. Endpoints

### 3.1 `GET /api/v1/me`
Identity of the caller.

```json
{
  "id": 12, "name": "Imran Sheikh", "email": "owner@nawabcaterers.pk",
  "phone": "+92 300 1234567", "role": "ROLE_TENANT_OWNER",
  "tenant_id": "01a1…", "is_super_admin": false, "is_customer": false,
  "vendor_category": "catering"
}
```

### 3.2 Hall bookings (legacy engine — concurrency-safe)

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/api/v1/bookings` | authenticated | Filters: `status`, `hall_id`, `date_from`, `date_to`. Paginated (20/page). |
| POST | `/api/v1/bookings` | `bookings.create` | Uses the atomic `BookingService::createReservation()`. `409` on slot conflict. |
| GET | `/api/v1/bookings/{id}` | scoped | Returns booking + payments + RSVPs + KOT + floor plan. |
| PATCH | `/api/v1/bookings/{id}` | `bookings.update` | `status`, `notes`, `advance_paid`. **Customers may only send `notes`.** |
| DELETE | `/api/v1/bookings/{id}` | Super Admin only | |
| GET | `/api/v1/bookings/availability` | authenticated | `hall_id`, `date`, optional `slot_type`. |

**Create**
```http
POST /api/v1/bookings
{ "hall_id": "uuid", "event_type": "Walima", "event_date": "2026-12-20",
  "slot_type": "EVENING", "guest_count": 400,
  "customer_name": "Ayesha Khan", "customer_phone": "+92 300 1234567" }
```
```json
{ "success": true, "booking": { "id": "…", "booking_reference": "RG-BK-…", "status": "CONFIRMED", "grand_total": 2450000.00 } }
```

### 3.3 Service bookings (multi-vendor engine)

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/api/v1/service-bookings` | scoped | Filters: `status`, `event_date_from`, `event_date_to` |
| GET | `/api/v1/service-bookings/{id}` | scoped | Includes the payment ledger |
| POST | `/api/v1/service-bookings/{id}/payments` | customer (own) / `payments.record` | Records a **PENDING** payment for verification |

```http
POST /api/v1/service-bookings/{id}/payments
{ "amount": 735000, "method": "CASH", "kind": "ADVANCE", "reference": "RCP-88213" }
```
```json
{ "success": true, "payment": { "id": "…", "status": "PENDING", "kind": "ADVANCE", "amount": "735000.00" },
  "message": "Payment submitted and awaiting staff verification." }
```
Only `CASH` is accepted for new records; `BANK_TRANSFER`, `CHEQUE` and all online method labels are rejected with **422**. A customer submission stays **PENDING** and never changes a balance; only authorized staff may verify it. Amounts above the outstanding balance are also rejected with **422**.

### 3.4 Payments (hall engine) — cash only

| Method | Path | Permission | Notes |
|---|---|---|---|
| GET | `/api/v1/payments` | scoped | Customers see only their own receipts; staff see their tenant |
| POST | `/api/v1/payments/record` | `payments.record` (staff) | Physically verified cash; atomically updates the booking ledger |
| POST | `/api/v1/payments/refund` | `payments.refund` (staff) | Authorized cash refund; reverses the ledger |
| GET | `/api/v1/payments/summary` | tenant staff | `period = DAILY|WEEKLY|MONTHLY|YEARLY`; refunds are netted out |

Recording a payment:
```http
POST /api/v1/payments/record
{ "booking_id": "<uuid>", "amount": 50000,
  "payment_type": "TOKEN_ADVANCE", "transaction_reference": "RCP-1042" }
```
The caller must be an active staff principal with the matching permission and tenant ownership of the booking. Overpayments, non-cash methods and customer-originated writes are rejected. Repeating a `transaction_reference` is idempotent — it never double-credits a booking. Every mutation writes a tamper-evident audit entry.

There are **no** online checkout, gateway-initiation, callback, webhook or automatic-verification endpoints. Historical rows with legacy method labels remain readable for reporting; new writes are cash-only.

---

## 4. Conventions

- **IDs**: UUIDs for marketplace objects, integers for legacy objects.
- **Money**: strings/decimal in JSON (`"2450000.00"`) — never binary floats.
- **Dates**: ISO-8601 with the `Asia/Karachi` offset.
- **Pagination**: Laravel paginator envelope (`data`, `links`, `meta`).
- **Errors**: `{"message": "…"}` for auth/business errors; `{"errors": {…}}` for validation (422).
- **Versioning**: the version is in the path (`/v1`), so a breaking change ships as `/v2` alongside `/v1`.

---

## 5. Mobile readiness checklist

| Requirement | State |
|---|---|
| Stateless auth | ✅ hashed bearer tokens, revocation, expiry |
| Scoped data access | ✅ tenant + customer scoping on every read |
| Offline-friendly reads | ✅ paginated, filterable list endpoints |
| Write path for payments | ✅ staff-only `POST /payments/record` / `POST /payments/refund`; customer manual submissions stay `PENDING` |
| Push notifications | ⛔ pending — the notification table exists, the transport does not |
| OAuth / social login | ⛔ pending (optional; local auth is complete) |
| Rate limiting | ✅ 60/min per token |

---

## 6. Example client flow

```bash
# 1. Issue a token from the signed-in web app
curl -X POST http://127.0.0.1:9000/account/api-tokens \
  -H 'Content-Type: application/json' -b 'riwaj_session=…' \
  -d '{"name":"Android phone","expires_in_days":90}'

# 2. Use it
curl http://127.0.0.1:9000/api/v1/me -H 'Authorization: Bearer <token>'

# 3. Browse the vendor's service bookings
curl 'http://127.0.0.1:9000/api/v1/service-bookings?status=ACCEPTED' \
  -H 'Authorization: Bearer <token>'
```
