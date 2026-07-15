# Cafe Staff Authorization — Phase 2: Branch-Level Data Isolation

**Date:** 2026-07-15
**Status:** Approved (design)
**Depends on:** Phase 1 (`2026-07-14-cafe-staff-authorization-design.md`, shipped) — `CafeContext`,
`CafeContextResolver`, `ResolvesCafeContext` trait, `EnsureCafePermission` middleware, and the
branch `{id}` guards on `CafeAdminController` (commit `d963416`).

## 1. Problem

Phase 1 gave delegated staff correct **resolution** (they see their cafe) and **permission gating**
(they reach only permitted endpoints), and it branch-scoped the `CafeAdminController` branch routes
(`list`, `switch`, and every branch `{id}` handler). It did **not** scope the *data* returned by the
other cafe-admin controllers. Today those controllers filter by **cafe** (all branches), using the
idiom `$cafe->branches()->pluck('id')` or cafe-wide service queries. A delegated staff member
assigned to Branch A can therefore still read bookings, matches, offers, occupancy, analytics, QR
history, and seating belonging to Branch B in the same cafe.

Phase 2 narrows every branch-owned data query from *all cafe branches* to the acting user's
`accessibleBranchIds`. Because an owner's `accessibleBranchIds` equals all cafe branches, **every
change is a no-op for owners** — owner behavior is provably unchanged.

## 2. Scope

Seven controller groups, one task each, sequenced most-sensitive first:

1. Bookings (customer PII, revenue)
2. Matches
3. QR scan (check-in integrity)
4. Occupancy
5. Analytics / Dashboard
6. Offers
7. Seating

One Phase 2 plan; each task is TDD and independently committable/deployable.

## 3. Mechanism

A single helper on the `ResolvesCafeContext` trait (peer of the Phase-1 `denyIfBranchInaccessible`):

```php
/** Accessible branch ids for the acting user (owner = all cafe branches). */
protected function accessibleBranchIds(Request $request): array
{
    return $this->cafeContext($request)?->accessibleBranchIds ?? [];
}
```

Every Phase-2 edit is exactly one of two shapes:

- **List / aggregate filter** — constrain the query with `whereIn('branch_id', $accessibleBranchIds)`
  (or the equivalent relationship constraint when the branch is reached via a relation, e.g. a
  booking through its `match`). This replaces the current "all cafe branches" assumption. Lists
  **narrow silently** — no 403.
- **Target guard** — for a single-resource read or any write, after the resource's branch is
  resolved, call the Phase-1 `denyIfBranchInaccessible($request, (int) $branchId)`. Returns **403**
  when the target's branch is outside the accessible set.

### 3.1 Response-code rule

- **Lists / aggregates:** filter the result set; never 403 for scoping.
- **Single-resource reads (`GET …/{id}`) and all writes:** **403** when the target's branch is not
  accessible. This is uniform with Phase 1's shipped `getBranch` (403), one rule to remember.
- Missing resource stays **404** (resolve the resource first, then guard).

## 4. Per-group edit map

Legend: **F** = list/aggregate filter, **G** = target guard.

### 4.1 Bookings (`BookingAdminController`, `BookingAdminService`)
- **F:** `index`, `todaySummary`, `exportReport`.
- **G:** `show`, `checkIn`, `cancel`.
- Branch linkage: a booking belongs to a branch via `branch_id` **or** via `match.branch_id`
  (see existing `getOwnerBooking`). The filter/guard must honor both paths:
  `whereIn('branch_id', $ids) OR whereHas('match', fn ($q) => $q->whereIn('branch_id', $ids))`.
- `BookingAdminService::listBookings(Cafe $cafe, array $filters)` gains the accessible-branch set
  (threaded as an explicit argument or via a `branch_ids` filter key) and applies it to its query.

### 4.2 Matches (`MatchAdminController`)
- **F:** `index`.
- **G:** `show`, `update`, `destroy`, `publish`, `updateScore`, `updateStatus`, `sendReminder`,
  `startMatch`, `endMatch`, `cancelMatch`, and `store` (the target branch must be accessible).
- Branch linkage: `game_matches.branch_id`.

### 4.3 QR scan (`QrScanController`)
- **F:** `recent`, `stats`.
- **G:** `scan`, `upload` — the scanned booking's branch (via `booking->branch` or
  `booking->match->branch`) must be in the accessible set, else reject (403). This closes
  cross-branch check-in.
- Branch linkage: through the resolved booking.

### 4.4 Occupancy (`OccupancyController`)
- **F:** `index`, `peakTimes`, `sections`.
- **G:** `updateCapacity` (target branch/section must be accessible).
- Branch linkage: seating section → `branch_id`.

### 4.5 Analytics / Dashboard (`AnalyticsController`, `DashboardController`)
- **F (aggregates over accessible branches only):** Analytics `overview`, `revenue`, `bookings`,
  `peakHours`, `customers`, `matches`, `chartData`, `topMatches`, `occupancy`, `exportReport`;
  Dashboard `index`, `upcomingMatches`, `recentBookings`.
- **G:** the `{branchId}` analytics variants guard the requested branch.
- Branch linkage: `branch_id` on the aggregated tables.

### 4.6 Offers (`OfferAdminController`, offer service)
- **F:** `index` / `list`, `listForBranch`.
- **G:** `show`, `update`, `destroy`, `updateStatus`, `uploadImage`, and the branch variants
  (`updateBranch`, `deleteBranch`, `toggleStatus`, `uploadImageBranch`, `storeForBranch`).
- **Cafe-wide offers subtlety:** `offers.branch_id` is nullable; a null `branch_id` is a
  cafe-level promotion visible to all staff. The offers list filter is therefore
  `whereIn('branch_id', $ids) OR whereNull('branch_id')` — **not** a plain `whereIn`. A guard on a
  null-branch offer passes for any staff of the cafe (it isn't branch-restricted). Every other
  group uses a plain `whereIn`.

### 4.7 Seating (`SeatingAdminController`)
- **F:** `listSections`, `listSeats`, `seatingLayout`.
- **G:** `createSection`, `updateSection`, `deleteSection`, `bulkAddSeats`, `bulkCreateSections`,
  `updateSeat`, `deleteSeat`, `toggleAvailability`.
- Branch linkage: section → `branch_id` (seats reached via their section).

## 5. Testing

Extend `tests/Feature/CafeAdmin` (in-memory SQLite, existing helpers `cafeWithOwner` /
`makeStaff`). Per group:

- **Owner regression:** owner still gets the full, unchanged result on a representative list and
  detail endpoint (proves no-op for owners).
- **Staff list narrowing:** staff assigned to Branch A, with data on both A and B, sees only A's
  rows on the list/aggregate endpoint.
- **Staff target guard:** staff gets **403** on an unassigned-branch `{id}` read and on a write.
- **Empty accessible set:** a staff member with a membership but zero branch assignments gets `[]`
  from lists and 403 from every target guard — `whereIn('branch_id', [])` returns no rows
  (fail-closed).
- **Offers cafe-wide:** staff sees a `branch_id = NULL` offer plus their own branch's offers, but
  not another branch's offer.

## 6. Edge cases & decisions

- **Fail-closed on empty set:** an empty `accessibleBranchIds` yields no rows and universal 403s;
  this is intended.
- **Owner no-op:** owner `accessibleBranchIds` = all cafe branches, asserted per group.
- **Booking dual linkage:** honor both `branch_id` and `match.branch_id` (§4.1).
- **Offers null branch:** cafe-wide offers remain visible to all staff (§4.6).
- **Response codes:** 403 for unassigned-branch single reads and writes; lists filter silently
  (§3.1).

## 7. Non-goals

- No change to Phase 1 behavior or the `CafeAdminController` branch `{id}` guards (already shipped).
- No Laravel Policy/Gate rewrite; no Eloquent global scopes (explicit inline filtering chosen for
  reviewability and to avoid affecting jobs/console/webhook queries).
- No new permissions, no multi-cafe staff, no web-dashboard or AWS changes.
- The branch-level staff-invite endpoints (`/branches/{branchId}/staff/*`) are untouched.

## 8. Deliverables

- `accessibleBranchIds()` helper on `ResolvesCafeContext`.
- Branch filtering/guards applied across the 7 controller groups (and the two services that own
  their queries: booking + offer).
- Feature tests per group in `tests/Feature/CafeAdmin`.
- Incremental commits (one per group); merge to `main` → Railway (testing) deploy.
