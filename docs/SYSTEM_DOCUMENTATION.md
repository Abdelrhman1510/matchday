# MatchDay — System Documentation

_Onboarding guide for QA / testers. Last updated: 2026-07-13._

For the endpoint-by-endpoint reference (all 192 endpoints, request bodies, params) see
**[API_REFERENCE.md](API_REFERENCE.md)**.

---

## 1. What is MatchDay?

MatchDay is a **football-cafe booking platform**. Fans discover cafes that screen live
matches, book seats for a specific match, pay, check in via QR, earn loyalty points and
achievements, and chat in match "fan rooms". Cafe owners manage their cafe, branches,
seating, matches, offers, bookings and **staff**. A platform admin oversees all cafes,
subscriptions and content through a separate web dashboard.

There are three surfaces:

| Surface | Who | Tech | Where |
|---|---|---|---|
| **REST API** (`/api/v1/...`) | Mobile app: fans, cafe owners, staff | Laravel + Sanctum | this repo |
| **Platform dashboard** (`/platform/...`) | Platform admins | Laravel Livewire (server-rendered) | this repo |
| Mobile app | End users | Flutter (separate repo) | — |

Testers will spend most time on the **REST API** (via Postman) and can also exercise the
**platform dashboard** in a browser.

---

## 2. Tech stack

- **Laravel 12**, PHP 8.2+ — API-only + a Livewire admin dashboard
- **Laravel Sanctum** — bearer-token auth for the API
- **Spatie laravel-permission** — roles & permissions
- **MySQL 8** (production/local); **SQLite in-memory** for the test suite
- **Laravel Reverb** — WebSockets (chat / fan rooms, live updates)
- **Laravel Scout** — full-text search (teams, cafes)
- **Simulated payment gateway** — payments are **faked** in every environment
  (`SimulatedPaymentGateway`); no real money moves. Treat "paid" bookings as test data.
- **Docker** — deployment container (`docker-entrypoint.sh` runs migrations + idempotent
  seeders on each boot)
- **Bilingual (EN/AR)** — many resources return both English and Arabic (`*_ar`) fields

---

## 3. Environments

All API paths are prefixed with **`/api/v1`**.

| Environment | Base URL | Notes |
|---|---|---|
| **Local** | `http://127.0.0.1:8000/api/v1` | `php artisan serve` |
| **Railway** (testing) | `https://web-production-2ef3c.up.railway.app/api/v1` | **Primary test target.** Auto-deploys from GitHub `main`. |
| **AWS** (production) | `https://tab3s.com/api/v1` (EIP `16.16.134.193`) | Live production — **do not use for test data.** |

> **Testers: use the Railway environment.** It is where new features land first. AWS is the
> live customer environment and must not be used for test/junk data.

Postman environment files are in `docs/`:
`Matchday_API.postman_environment.json` (local),
`Matchday_API_Railway.postman_environment.json` (Railway),
`Matchday_API_AWS.postman_environment.json` (AWS).

---

## 4. Getting started (Postman)

1. **Import** the collection `docs/Matchday_API_Complete.postman_collection.json`.
2. **Import** an environment (start with **Railway**) and select it.
3. Log in to get a token (see §5), then set the environment's **`token`** variable to the
   returned token. Every authenticated request uses `Authorization: Bearer {{token}}`.
4. Start hitting endpoints. The collection is organized into the same folders as
   [API_REFERENCE.md](API_REFERENCE.md).

### Seeded test accounts (local / freshly-seeded DB)

| Role | Email | Password |
|---|---|---|
| Fan | `ahmed@matchday.app` | `password` |
| Cafe owner | `omar@matchday.app` | `password` |
| Platform admin | `admin@tab3s.com` | _set out-of-band — ask the team_ |

> These are seed defaults for dev/testing. On Railway the data set may differ; ask the team
> for a current owner/fan test login if these don't work.

---

## 5. Authentication flow

Auth is **token-based (Sanctum)**. Typical fan journey:

1. `POST /auth/register` — name, email, password → sends an **OTP** to verify the email.
2. `POST /auth/verify-email` — email + OTP → marks the account verified.
3. `POST /auth/login` — email + password → returns a **bearer token**.
4. Use `Authorization: Bearer <token>` on all protected endpoints.
5. `GET /auth/me` — current user; `POST /auth/refresh` — rotate token; `POST /auth/logout`.

Other entry points:

- `POST /auth/register/cafe-owner` — cafe-owner registration (OTP-verified) → yields a
  `cafe_owner` account that can access `/cafe-admin/*`.
- `POST /auth/login/google`, `POST /auth/login/apple` — social login.
- `POST /auth/forgot-password` → `POST /auth/reset-password` (OTP-based).
- **Dev helper:** `GET /debug/otp/{email}` returns the current OTP from cache so testers
  don't need real email delivery. _(Debug-only; remove before real production.)_

**In Postman:** after Login, copy `data.token` into the environment `token` variable.

---

## 6. Roles & permissions

Four roles (Spatie + a `users.role` column):

| Role | Can do |
|---|---|
| `fan` | Browse, book, pay, chat, loyalty, profile |
| `cafe_owner` | Everything under `/cafe-admin/*` for **their own** cafe |
| `staff` | Scoped cafe-admin actions per their granted permissions + assigned branches |
| `platform_admin` | The `/platform` web dashboard (manage all cafes, plans, content) |

`/cafe-admin/*` routes require the `cafe.owner` middleware **and** a specific permission.
The permission catalog (what a staff member can be granted):

```
manage-bookings, view-bookings, manage-matches, view-analytics, manage-offers,
manage-menu, manage-branches, manage-seating, manage-subscription, scan-qr,
check-in-customers, view-occupancy, manage-cafe-profile, manage-staff,
manage-inventory, process-payments, full-admin-access
```

A cafe owner implicitly holds all management permissions. **Staff** get a role
(`admin`/`manager`/`staff`) plus an explicit permission set and are assigned to **one or
more branches** (see the Staff Management endpoints).

---

## 7. API conventions

**Standard response envelope:**

```json
{ "success": true, "message": "...", "data": { }, "meta": { } }
```

- **Success** → `success: true`, payload in `data` (object or array). List endpoints add a
  `meta` block (pagination / counts).
- **Validation error** → HTTP **422**:
  ```json
  { "success": false, "message": "Validation failed", "errors": { "field": ["reason"] } }
  ```
- **Auth error** → **401** (no/invalid token) or **403** (authenticated but not permitted).
- **Not found** → **404**. **Rate limited** → **429**.

**Rate limiting:** `throttle:api` — ~**60 req/min** authenticated, **30 req/min** guests.

**Bilingual fields:** many resources return Arabic alongside English, e.g. `name` /
`name_ar`, `title` / `title_ar`, `league` / `league_ar`, plan `features` / `features_ar`.
Absent translations fall back to English.

**IDs & currency:** money is in **SAR**; some legacy responses expose amounts in cents —
check each endpoint's description in the reference.

---

## 8. Core domain model (what to test)

```
Cafe ──< Branch ──< SeatingSection ──< Seat
  │         │
  │         └──< GameMatch (a screening) ──< Booking ──< Payment (simulated)
  │
  ├──< StaffMember (role) ──< branch assignments (branch_staff)
  ├──< Offer
  └──< CafeSubscription ── SubscriptionPlan (limits: branches, staff, matches, bookings…)
Fan (User) ── LoyaltyPoints/Tier, Achievements, SavedCafes, Notifications, Chat/FanRoom
```

Testing hotspots and gotchas:

- **Subscriptions gate cafe-admin capacity.** Adding staff/branches/matches is blocked
  (HTTP 403) unless the owner's cafe has an **active subscription** whose plan allows it.
- **Staff management** (`/cafe-admin/staff`): owner sets the staff **email + password**
  directly (account active immediately, no invite email), assigns **role + permissions +
  one or more branches**. Email must be brand-new (422 if taken); each `branch_id` must
  belong to the owner's cafe (422 otherwise). See the reference for exact bodies.
- **Bookings** require an available seat on a specific match; **check-in** is via QR scan.
- **Payments are simulated** — expect deterministic "success" without a real gateway.
- **Email verification** may be required before some actions; use `/debug/otp/{email}`.

---

## 9. Platform admin dashboard (browser)

Separate from the API: server-rendered Livewire at **`/platform/login`** →
`/platform/dashboard`. Sections: cafes, bookings, matches, subscriptions/plans, reports,
analytics, settings. It is **bilingual** — **Settings → Platform language → العربية**
switches the whole dashboard to Arabic + RTL. Subscription plan names/features, flash
messages, validation, and currency (`ريال سعودي`) are all localized.

Log in with the platform admin account (§4).

---

## 10. Running the test suite (developers)

```bash
composer install
php artisan test                 # full suite (SQLite in-memory, no external DB needed)
php artisan test --filter=StaffManagementTest   # a single class
```

Feature tests live in `tests/Feature/`. They seed roles/permissions per test and use model
factories; no MySQL required.

---

## 11. Handy references

- **Full endpoint reference:** [API_REFERENCE.md](API_REFERENCE.md)
- **Postman collection:** `docs/Matchday_API_Complete.postman_collection.json`
- **Environments:** `docs/Matchday_API*.postman_environment.json`
- **Feature specs/plans:** `docs/superpowers/specs/`, `docs/superpowers/plans/`
- **Deployment:** Railway auto-deploys `main`; AWS (tab3s.com) is a manual deploy.

---

## 12. Reporting bugs

When filing a bug, include: environment (Railway/AWS/local), the **request** (method, full
path, headers minus token, body), the **actual response** (status + body), the **expected**
result, and the **account/role** used. For dashboard bugs, note the **language** (EN/AR) and
page.
