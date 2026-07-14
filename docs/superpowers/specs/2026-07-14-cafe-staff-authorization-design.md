# Cafe Staff Authorization & Branch Scoping (API)

**Date:** 2026-07-14
**Status:** Approved design, ready for implementation planning
**Scope:** matchday-api only. API (`/api/v1/cafe-admin/*`). Railway deploy. AWS untouched.

## 1. Goal

Make a **staff member** who logs in with owner-issued credentials actually able to operate
the cafe-admin API, correctly bounded:

1. **See their cafe** — the cafe the owner added them to.
2. **See/act on only their assigned branches** — the branches recorded in `branch_staff`.
3. **Use only their granted permissions** — every action they lack permission for returns 403.

Today none of this works: `/cafe-admin/*` controllers resolve the cafe with
`$request->user()->ownedCafes()->first()`, which is empty for staff, so staff get
"No cafe found" / "not the cafe owner" on every action.

## 2. Current state

- Group middleware `cafe.owner` (`EnsureCafeOwner`) already lets a staff member *into* the
  `/cafe-admin` group (it allows any user with a `branch_staff` row), but the controllers
  then reject them.
- **~68 call sites** of `$request->user()->ownedCafes()->first()` across 12 controllers
  (`CafeAdminController`, `BookingAdminController`, `MatchAdminController`,
  `SeatingAdminController`, `OccupancyController`, `OfferAdminController`, `QrScanController`,
  `AnalyticsController`, `DashboardController`, `SubscriptionController`, `BillingController`,
  `StaffController`).
- Most cafe-admin endpoints do **not** check granular permissions — they assume "cafe_owner ⇒
  can do everything for their cafe." Only `StaffController` checks `manage-staff`.
- `User` already has `ownedCafes()`, `staffMemberships()` (→ `StaffMember`), and
  `branchAssignments()` (→ `Branch` via `branch_staff`). `StaffMember` has `cafe()`.
- Permission checks go through `User::can()` / `User::hasPermissionTo()` (role-based fallback
  for `cafe_owner`/`admin`, plus per-user grants). The permission catalog:
  `manage-bookings, view-bookings, manage-matches, view-analytics, manage-offers, manage-menu,
  manage-branches, manage-seating, manage-subscription, scan-qr, check-in-customers,
  view-occupancy, manage-cafe-profile, manage-staff, manage-inventory, process-payments,
  full-admin-access`.
- `current_branch_id` is a column on **`cafes`** (cafe-level "current branch"), set via
  `PUT /cafe-admin/current-branch`.

**Assumption:** a staff member belongs to **one** cafe (resolver takes their first accepted
`StaffMember`). Multi-cafe staff is out of scope.

## 3. Architecture (Approach A)

Three isolated pieces; controller bodies change only where they resolve the cafe or filter by
branch.

### 3.1 `CafeContextResolver` (service) + `ResolvesCafeContext` (trait)

A service that, given the authenticated user, returns a `CafeContext`:

```
CafeContext {
    Cafe    cafe;                 // the acting cafe
    bool    isOwner;              // cafe.owner_id === user.id
    int[]   accessibleBranchIds;  // owner → all cafe branches; staff → assigned branches ∩ cafe
    string[] permissions;         // effective permissions of the acting user (for guardrails)
}
```

Resolution:

```
cafe:  owner → user.ownedCafes()->first()
       staff → user.staffMemberships()->where(invitation_status,'accepted')->first()?->cafe
       else  → null

accessibleBranchIds:
       owner → cafe.branches()->pluck('id')
       staff → user.branchAssignments()->whereIn('branches.id', cafe.branches ids)->pluck('id')
```

The result is **memoized on the request** (`$request->attributes`) so the middleware and the
controllers resolve once. The trait `ResolvesCafeContext` is added to the base
`App\Http\Controllers\Controller`, exposing `protected function cafeContext(Request): ?CafeContext`
and a convenience `protected function actingCafe(Request): ?Cafe`. Every
`$request->user()->ownedCafes()->first()` becomes `$this->actingCafe($request)` (returns the
same Cafe for an owner — zero behavior change for owners).

### 3.2 `cafe.permission` middleware

Registered alias `cafe.permission`, applied per route: `cafe.permission:manage-matches`.
A special token `owner` means owner-only.

```
handle(request, next, permission):
    ctx = CafeContextResolver.resolve(request.user())
    if ctx == null:            return 404 "No cafe found."
    if permission == 'owner':  return ctx.isOwner ? next : 403
    if ctx.isOwner:            return next            // owner bypasses all permission gates
    if request.user().can(permission): return next   // staff must hold the permission
    return 403 "You do not have permission to perform this action."
```

Routes needing only cafe membership (see §4) get **no** `cafe.permission` middleware — the
group's `cafe.owner` already admits owner + assigned staff.

### 3.3 Branch scoping

`accessibleBranchIds` drives two things:
- **Which branches a staff may target/switch to** (Phase 1).
- **Which rows a staff may read/write** for branch-owned data (Phase 2).

## 4. Authorization map

Rule of thumb: **if a `view-*` permission exists for the domain, reads require it; otherwise
reads require only cafe membership** (and are still branch-scoped). Writes require the matching
`manage-*`. Owner bypasses every permission gate.

| Route(s) | Gate |
|---|---|
| `GET /cafe`, `GET /onboarding-status`, `GET /current-branch`, `PUT /current-branch`, `GET /roles-permissions` | **membership** (any assigned staff or owner) |
| `POST /cafe` (createCafe) | **owner** |
| `PUT /cafe`, `POST /cafe/logo` | `manage-cafe-profile` |
| `GET /branches`, `GET /branches/{id}`, `GET /branches/{id}/overview`, `GET /branches/{id}/setup-progress`, `GET /branches/{id}/amenities` | **membership** (branch-scoped) |
| `POST /branches`, `PUT /branches/{id}`, `DELETE /branches/{id}`, `PUT /branches/{id}/hours`, `POST/DELETE …/amenities…`, `PUT /branches/{id}/status` | `manage-branches` |
| Seating reads (`GET …/sections`, `GET …/seats`) | **membership** (branch-scoped) |
| Seating writes (create/update/delete section & seats, bulk) | `manage-seating` |
| Matches reads (`GET /matches`, `GET /matches/{id}`) | **membership** (branch-scoped) |
| Matches writes (store/update/destroy/publish/score/status/reminder) | `manage-matches` |
| `GET /bookings`, `GET /bookings/{id}`, `GET /bookings/today-summary` | `view-bookings` |
| `POST /bookings/{id}/check-in` | `check-in-customers` |
| `POST /bookings/{id}/cancel` | `manage-bookings` |
| `POST /scan-qr`, `/scan-qr/upload`, `GET /scan-qr/recent`, `/scan-qr/stats` | `scan-qr` |
| `GET /occupancy`, `/occupancy/peak-times`, `/occupancy/sections` | `view-occupancy` |
| `PUT /occupancy/capacity` | `manage-seating` |
| Offers reads (`GET /offers`, `GET /offers/{id}`) | **membership** (branch-scoped) |
| Offers writes (store/update/destroy/status/upload-image) | `manage-offers` |
| `GET /dashboard*`, `GET /analytics/*` | `view-analytics` |
| `GET /staff`, `GET /staff/{id}` | `manage-staff` (read) |
| `POST /staff`, `PUT /staff/{id}`, `DELETE /staff/{id}`, `POST /staff/{id}/resend-invite` | `manage-staff` **+ guardrails (§5)** |
| `GET/POST/PUT /subscription*`, all `/billing/*`, `GET /subscription/usage` | **owner** |

## 5. Staff-management delegation guardrails

Owner: no restrictions. A **non-owner** staff member holding `manage-staff` may create/update/
remove staff **subject to all** of these (else `422`/`403`):

1. **No admin creation/elevation** — `role` may only be `manager` or `staff`; may not set
   `admin`. (422)
2. **No privilege escalation** — every entry in `permissions[]` must be a subset of the acting
   staff member's own effective permissions; granting a permission they lack → 422 (lists the
   offending permissions).
3. **Cannot touch admins** — may not update/remove a `StaffMember` whose `role` is `admin`. (403)
4. **Branch bounds** — `branch_ids` must be a subset of the acting staff member's
   `accessibleBranchIds`. (422)
5. The owner is not a `StaffMember`, so "cannot modify the owner" holds automatically.

These checks live in `StaffController` (owner short-circuits them) using the `CafeContext`.

## 6. Branch scoping details

### Phase 1
- `PUT /current-branch` (switchCurrentBranch): the target branch must be in
  `accessibleBranchIds`, else 403. (Owner: any cafe branch.)
- `GET /branches` (listBranches): staff see only `accessibleBranchIds`; owner sees all.
- Any route with a `{id}`/`{branchId}` that **is a branch** verifies the branch ∈
  `accessibleBranchIds` for staff (branch-target guard), else 403/404.

### Phase 2 — filter branch-owned data by `accessibleBranchIds`
Apply an `whereIn(branch_id, accessibleBranchIds)` (or the equivalent relationship constraint)
to the queries in:
- **Bookings** — `index`, `show`, `today-summary`, and target-branch checks on `check-in`/`cancel`.
- **Matches** — `index`, `show`, and branch checks on write ops (a match belongs to a branch).
- **Seating** — sections/seats resolved via their branch.
- **Occupancy** — dashboard/sections/peak-times/capacity keyed by accessible branches.
- **Offers** — offers scoped to accessible branches (offers that target a branch).
- **Analytics / Dashboard** — aggregates computed over accessible branches only.
- **QR scan** — scans validated against accessible branches.

Owner's `accessibleBranchIds` = all cafe branches, so Phase 2 filtering is a **no-op for
owners** (their result set is unchanged) — this keeps owner behavior identical.

## 7. Error handling

- Not owner and lacks the required permission → **403**
  `{ "success": false, "message": "You do not have permission to perform this action." }`
- Owner-only route hit by staff → **403** (same shape).
- No resolvable cafe → **404** `{ "success": false, "message": "No cafe found." }`.
- Targeting a branch/resource outside `accessibleBranchIds` → **403** (or **404** for a
  detail/`show` route, to avoid leaking existence — pick 404 for `GET …/{id}` reads, 403 for
  writes).

## 8. Testing

Feature tests (extend `tests/Feature/CafeAdmin`, in-memory SQLite):

- **Owner regression:** owner retains full access to a representative endpoint per controller
  (no behavior change).
- **Resolution:** a staff member resolves *their* cafe (not null) on a membership endpoint
  (`GET /cafe`).
- **Permission gate:** staff **with** `manage-matches` can `POST /matches`; staff **without**
  it → 403; same pattern for bookings (`view-bookings`), offers, occupancy, analytics.
- **Owner-only:** staff (even with `manage-subscription`) → 403 on `/subscription/*` and
  `/billing/*`.
- **Branch scope (P1):** staff cannot `switchCurrentBranch` to an unassigned branch (403);
  `listBranches` returns only assigned branches.
- **Branch scope (P2):** staff `GET /bookings` (and matches/occupancy) returns only
  assigned-branch rows; targeting an unassigned branch’s resource → 403/404.
- **Staff-mgmt guardrails:** non-owner `manage-staff` cannot create an `admin` (422), cannot
  grant a permission they lack (422), cannot modify an admin staff (403), cannot assign an
  unassigned branch (422); owner can do all of these.

## 9. Implementation phases

- **Phase 1 — foundation & gating:** `CafeContext` + `CafeContextResolver` + `ResolvesCafeContext`
  trait; replace all `ownedCafes()->first()` with `actingCafe()`; `cafe.permission` middleware
  (incl. `owner` token) applied across the route map (§4); Phase-1 branch rules (§6.1); staff-mgmt
  guardrails (§5). Deliverable: staff can log in, see their cafe + assigned branches, and are
  permission-gated; owners unchanged.
- **Phase 2 — data isolation:** branch-scope the data queries per controller group (§6.2),
  one group per task.

## 10. Non-goals

- No Laravel Policy/Gate rewrite (middleware + service instead).
- No multi-cafe staff.
- No new permissions beyond the existing catalog.
- No web-dashboard or AWS changes.
- The separate branch-level invite endpoints (`/branches/{branchId}/staff/*`) are untouched.
