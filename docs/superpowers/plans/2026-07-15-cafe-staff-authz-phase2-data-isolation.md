# Cafe Staff Authz — Phase 2: Branch-Level Data Isolation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Narrow every branch-owned data query in the cafe-admin controllers from *all cafe branches* to the acting user's `accessibleBranchIds`, so delegated staff see and act on only their assigned branches' data.

**Architecture:** One helper (`accessibleBranchIds()`) on the existing `ResolvesCafeContext` trait feeds two edit shapes: **list/aggregate filters** (`whereIn('branch_id', $ids)`, silent narrowing) and **target guards** (`denyIfBranchInaccessible()` from Phase 1, returns 403). Owners' accessible set equals all cafe branches, so every change is a no-op for owners.

**Tech Stack:** Laravel 12, PHP 8.2, Sanctum, Spatie permissions, PHPUnit 11 (in-memory SQLite, RefreshDatabase).

## Global Constraints

- Deploy target: **Railway (testing) only** — never AWS/tab3s.com. Push from `main`.
- **403** for unassigned-branch single-resource reads and writes; lists filter silently (no 403).
- Missing resource stays **404** (resolve first, then guard).
- Owner behavior must be provably unchanged — assert an owner regression per task.
- Backward-compatible service signatures: thread branch ids via an optional `$filters['branch_ids']` key or an optional trailing `?array $branchIds = null` param, defaulting to the existing cafe-wide behavior when absent.
- Existing helpers available on every cafe-admin controller (via base `Controller` → `ResolvesCafeContext`): `actingCafe($request)`, `cafeContext($request)`, and Phase-1 `denyIfBranchInaccessible($request, int $branchId): ?JsonResponse`.
- Resolver fact: owner `accessibleBranchIds` = all cafe branch ids; staff = assigned ∩ cafe branches (may be empty → fail-closed).

---

## Task 1: `accessibleBranchIds()` helper + Bookings isolation

**Files:**
- Modify: `app/Http/Controllers/Concerns/ResolvesCafeContext.php`
- Modify: `app/Services/BookingAdminService.php` (`listBookings`)
- Modify: `app/Http/Controllers/BookingAdminController.php` (`index`, `show`, `checkIn`, `cancel`, `todaySummary`, `exportReport`)
- Create: `tests/Feature/CafeAdmin/BranchDataIsolationTest.php`

**Interfaces:**
- Produces: `accessibleBranchIds(Request $request): array` on the trait — returns the acting user's accessible branch ids (owner = all cafe branches; `[]` when no context). Consumed by all later tasks.
- Consumes: Phase-1 `denyIfBranchInaccessible($request, int $branchId): ?JsonResponse`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CafeAdmin/BranchDataIsolationTest.php`:

```php
<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\GameMatch;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchDataIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Cafe, 2: Branch, 3: Branch} owner, cafe, branchA, branchB */
    protected function isolationCafe(): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branchA = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $branchB = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $plan = SubscriptionPlan::factory()->create([
            'is_active' => true, 'has_analytics' => true, 'max_staff_members' => 10,
        ]);
        CafeSubscription::factory()->create([
            'cafe_id' => $cafe->id, 'plan_id' => $plan->id, 'status' => 'active',
            'starts_at' => now()->subMonth(), 'expires_at' => now()->addMonth(),
        ]);
        return [$owner, $cafe, $branchA, $branchB];
    }

    protected function makeStaff(Cafe $cafe, array $branchIds, array $permissions, string $role = 'manager'): User
    {
        $staff = User::factory()->staff()->create();
        $staff->staffMemberships()->create([
            'cafe_id' => $cafe->id, 'role' => $role, 'invitation_status' => 'accepted',
        ]);
        foreach ($branchIds as $bid) {
            $staff->branchAssignments()->attach($bid, ['role' => $role]);
        }
        foreach ($permissions as $p) {
            $staff->givePermissionTo($p);
        }
        return $staff;
    }

    /** @test */
    public function staff_bookings_list_shows_only_assigned_branch()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $matchA = GameMatch::factory()->create(['branch_id' => $branchA->id]);
        $matchB = GameMatch::factory()->create(['branch_id' => $branchB->id]);
        $bookingA = Booking::factory()->create(['branch_id' => $branchA->id, 'match_id' => $matchA->id]);
        $bookingB = Booking::factory()->create(['branch_id' => $branchB->id, 'match_id' => $matchB->id]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['view-bookings']);
        Sanctum::actingAs($staff);

        $res = $this->getJson('/api/v1/cafe-admin/bookings')->assertStatus(200);
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($bookingA->id, $ids);
        $this->assertNotContains($bookingB->id, $ids);
    }

    /** @test */
    public function staff_cannot_show_unassigned_branch_booking()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $matchB = GameMatch::factory()->create(['branch_id' => $branchB->id]);
        $bookingB = Booking::factory()->create(['branch_id' => $branchB->id, 'match_id' => $matchB->id]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['view-bookings']);
        Sanctum::actingAs($staff);

        $this->getJson("/api/v1/cafe-admin/bookings/{$bookingB->id}")->assertStatus(403);
    }

    /** @test */
    public function owner_bookings_list_shows_all_branches()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $matchA = GameMatch::factory()->create(['branch_id' => $branchA->id]);
        $matchB = GameMatch::factory()->create(['branch_id' => $branchB->id]);
        $bookingA = Booking::factory()->create(['branch_id' => $branchA->id, 'match_id' => $matchA->id]);
        $bookingB = Booking::factory()->create(['branch_id' => $branchB->id, 'match_id' => $matchB->id]);
        Sanctum::actingAs($owner);

        $res = $this->getJson('/api/v1/cafe-admin/bookings')->assertStatus(200);
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($bookingA->id, $ids);
        $this->assertContains($bookingB->id, $ids);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter=BranchDataIsolationTest`
Expected: `staff_bookings_list_shows_only_assigned_branch` FAILS (booking B present), `staff_cannot_show_unassigned_branch_booking` FAILS (200 not 403). Owner test passes.

- [ ] **Step 3: Add the `accessibleBranchIds()` helper to the trait**

In `app/Http/Controllers/Concerns/ResolvesCafeContext.php`, add after `actingCafe()`:

```php
    /**
     * Accessible branch ids for the acting user.
     * Owner = all cafe branches; staff = assigned branches within the cafe;
     * [] when no cafe context resolves (callers guard the no-cafe case with 404 first).
     */
    protected function accessibleBranchIds(Request $request): array
    {
        return $this->cafeContext($request)?->accessibleBranchIds ?? [];
    }
```

- [ ] **Step 4: Scope `BookingAdminService::listBookings` by branch ids**

In `app/Services/BookingAdminService.php`, replace the first line of `listBookings`:

```php
        $branchIds = $cafe->branches()->pluck('id');
```

with:

```php
        // Phase 2: caller may pass an explicit accessible-branch set; default to all cafe branches.
        $branchIds = array_key_exists('branch_ids', $filters)
            ? collect($filters['branch_ids'])
            : $cafe->branches()->pluck('id');
```

(The rest of the method — the `whereIn` / `orWhereHas('match')` block and `getReturningUserIds($branchIds, …)` — already consumes `$branchIds`, so no other change is needed.)

- [ ] **Step 5: Pass accessible ids from `index` and guard `show`/`checkIn`/`cancel`/summaries**

In `app/Http/Controllers/BookingAdminController.php` `index`, change the service call:

```php
        $result = $this->bookingService->listBookings($cafe, [
            'status' => $request->query('status'),
            'match_id' => $request->query('match_id'),
            'date' => $request->query('date'),
            'per_page' => $request->query('per_page', 15),
        ]);
```

to add the branch set:

```php
        $result = $this->bookingService->listBookings($cafe, [
            'status' => $request->query('status'),
            'match_id' => $request->query('match_id'),
            'date' => $request->query('date'),
            'per_page' => $request->query('per_page', 15),
            'branch_ids' => $this->accessibleBranchIds($request),
        ]);
```

In `getOwnerBooking` (the shared resolver), narrow to accessible branches so `show`/`checkIn`/`cancel` (which all resolve through it and then 404 on null) fail-closed — replace:

```php
        $branchIds = $cafe->branches()->pluck('id');
```

with:

```php
        $branchIds = $this->accessibleBranchIds($request);
```

Then, to return **403** (not 404) for a booking that exists in the cafe but on an unassigned branch, in each of `show`, `checkIn`, `cancel`, immediately BEFORE the existing `getOwnerBooking` call add a cafe-scoped existence probe + guard. Concretely, at the top of `show(Request $request, int $id)` (and identically in `checkIn` and `cancel`), after the permission check and before `$booking = $this->getOwnerBooking(...)`, insert:

```php
        $cafe = $this->getOwnerCafe($request);
        if ($cafe) {
            $cafeBranchIds = $cafe->branches()->pluck('id');
            $inCafe = \App\Models\Booking::where(function ($q) use ($cafeBranchIds) {
                $q->whereIn('branch_id', $cafeBranchIds)
                  ->orWhereHas('match', fn ($m) => $m->whereIn('branch_id', $cafeBranchIds));
            })->with('match:id,branch_id')->find($id);
            if ($inCafe) {
                $branchId = $inCafe->branch_id ?? $inCafe->match?->branch_id;
                if ($branchId && ($deny = $this->denyIfBranchInaccessible($request, (int) $branchId))) {
                    return $deny;
                }
            }
        }
```

For `todaySummary` and `exportReport`, scope their queries to accessible branches — replace the `$cafe->branches()->pluck('id')` occurrence in each with `$this->accessibleBranchIds($request)`.

- [ ] **Step 6: Run the tests to verify they pass**

Run: `php artisan test --filter=BranchDataIsolationTest`
Expected: PASS (3 tests).

- [ ] **Step 7: Run the cafe-admin suite for regressions**

Run: `php artisan test tests/Feature/CafeAdmin`
Expected: PASS (previous count + 3).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Concerns/ResolvesCafeContext.php app/Services/BookingAdminService.php app/Http/Controllers/BookingAdminController.php tests/Feature/CafeAdmin/BranchDataIsolationTest.php
git commit -m "feat(staff-authz): branch-isolate bookings data (Phase 2 task 1)"
```

---

## Task 2: Matches isolation

**Files:**
- Modify: `app/Services/MatchAdminService.php` (`listMatches`)
- Modify: `app/Http/Controllers/MatchAdminController.php` (`index`, `getOwnerMatch`, and write handlers)
- Test: `tests/Feature/CafeAdmin/BranchDataIsolationTest.php` (append)

**Interfaces:**
- Consumes: `accessibleBranchIds($request)`, `denyIfBranchInaccessible($request, int $branchId)`.

- [ ] **Step 1: Write the failing test**

Append to `BranchDataIsolationTest`:

```php
    /** @test */
    public function staff_matches_list_shows_only_assigned_branch()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $matchA = GameMatch::factory()->create(['branch_id' => $branchA->id]);
        $matchB = GameMatch::factory()->create(['branch_id' => $branchB->id]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['manage-matches']);
        Sanctum::actingAs($staff);

        $res = $this->getJson('/api/v1/cafe-admin/matches')->assertStatus(200);
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($matchA->id, $ids);
        $this->assertNotContains($matchB->id, $ids);
    }

    /** @test */
    public function staff_cannot_modify_unassigned_branch_match()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $matchB = GameMatch::factory()->create(['branch_id' => $branchB->id, 'is_published' => false]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['manage-matches']);
        Sanctum::actingAs($staff);

        $this->getJson("/api/v1/cafe-admin/matches/{$matchB->id}")->assertStatus(403);
        $this->putJson("/api/v1/cafe-admin/matches/{$matchB->id}/publish")->assertStatus(403);
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="BranchDataIsolationTest::staff_matches_list_shows_only_assigned_branch"`
Expected: FAIL — match B present in list.

- [ ] **Step 3: Scope `listMatches` and narrow `getOwnerMatch`**

In `app/Services/MatchAdminService.php` `listMatches`, replace:

```php
        $branchIds = $cafe->branches()->pluck('id');
```

with:

```php
        $branchIds = array_key_exists('branch_ids', $filters)
            ? collect($filters['branch_ids'])
            : $cafe->branches()->pluck('id');
```

In `app/Http/Controllers/MatchAdminController.php` `index`, add `'branch_ids' => $this->accessibleBranchIds($request),` to the `$this->matchService->listMatches($cafe, [ ... ])` filter array (alongside `status`/`branch_id`/`per_page`).

- [ ] **Step 4: Guard the match write/read handlers (403 for unassigned)**

In `MatchAdminController`, `getOwnerMatch` stays cafe-scoped (so truly-missing → 404). In each handler that resolves a match — `show`, `update`, `destroy`, `publish`, `updateScore`, `updateStatus`, `sendReminder`, `startMatch`, `endMatch`, `cancelMatch` — immediately AFTER the existing `if (!$match) { …404… }` block, insert:

```php
        if ($deny = $this->denyIfBranchInaccessible($request, (int) $match->branch_id)) {
            return $deny;
        }
```

For `store(Request $request, $branchId = null)`: after the target branch is determined (the `branch_id` used to create the match — the request `branch_id` or the cafe's current branch), and after confirming it belongs to the cafe, add the same guard on that branch id:

```php
        if ($deny = $this->denyIfBranchInaccessible($request, (int) $targetBranchId)) {
            return $deny;
        }
```

where `$targetBranchId` is the resolved branch id the match will be created under.

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=BranchDataIsolationTest`
Expected: PASS.

- [ ] **Step 6: Regression + commit**

```bash
php artisan test tests/Feature/CafeAdmin
git add app/Services/MatchAdminService.php app/Http/Controllers/MatchAdminController.php tests/Feature/CafeAdmin/BranchDataIsolationTest.php
git commit -m "feat(staff-authz): branch-isolate matches data (Phase 2 task 2)"
```

---

## Task 3: QR scan isolation

**Files:**
- Modify: `app/Services/QrScanService.php` (`getRecentScans`, `getScanStats`)
- Modify: `app/Http/Controllers/QrScanController.php` (`scan`, `recent`, `stats`, `upload`)
- Test: `tests/Feature/CafeAdmin/BranchDataIsolationTest.php` (append)

**Interfaces:**
- Consumes: `accessibleBranchIds($request)`, `denyIfBranchInaccessible($request, int $branchId)`.

- [ ] **Step 1: Write the failing test**

Append to `BranchDataIsolationTest`:

```php
    /** @test */
    public function staff_cannot_scan_unassigned_branch_booking()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $matchB = GameMatch::factory()->create(['branch_id' => $branchB->id]);
        $bookingB = Booking::factory()->create([
            'branch_id' => $branchB->id, 'match_id' => $matchB->id, 'status' => 'confirmed',
        ]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['scan-qr']);
        Sanctum::actingAs($staff);

        $this->postJson('/api/v1/cafe-admin/scan-qr', ['qr_data' => $bookingB->booking_code])
            ->assertStatus(403);
    }
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter="BranchDataIsolationTest::staff_cannot_scan_unassigned_branch_booking"`
Expected: FAIL — scan succeeds (200) instead of 403.

- [ ] **Step 3: Guard `scan` and `upload` by the booking's branch**

In `app/Http/Controllers/QrScanController.php` `scan`, after the booking is resolved and the cafe is confirmed (the `$cafe = $this->getOwnerCafe($request);` path and the fallback that derives cafe from the booking) but BEFORE processing the check-in, resolve the target booking and guard its branch. Concretely, after the input is validated and the booking located as `$booking` (via `Booking::where('booking_code', $qrInput)->first()`), add:

```php
        $targetBranchId = $booking?->branch_id ?? $booking?->match?->branch_id;
        if ($targetBranchId && ($deny = $this->denyIfBranchInaccessible($request, (int) $targetBranchId))) {
            return $deny;
        }
```

Apply the identical guard in `upload` after its booking is resolved.

- [ ] **Step 4: Scope `recent` and `stats` service calls**

In `app/Services/QrScanService.php`, change the signatures and queries.

`getRecentScans` — signature `public function getRecentScans(Cafe $cafe, int $limit = 10, ?array $branchIds = null): array`, and change the query head:

```php
        $scans = QrScanLog::where('cafe_id', $cafe->id)
```

to:

```php
        $scans = QrScanLog::where('cafe_id', $cafe->id)
            ->when($branchIds !== null, function ($q) use ($branchIds) {
                $q->whereHas('booking', function ($b) use ($branchIds) {
                    $b->where(function ($bb) use ($branchIds) {
                        $bb->whereIn('branch_id', $branchIds)
                           ->orWhereHas('match', fn ($m) => $m->whereIn('branch_id', $branchIds));
                    });
                });
            })
```

`getScanStats` — signature `public function getScanStats(Cafe $cafe, ?array $branchIds = null): array`; include `$branchIds` in the cache key and apply the same `->when($branchIds !== null, …)` `whereHas('booking', …)` constraint to the `$todayScans` query. Cache key line becomes:

```php
        $cacheKey = "qr_scan_stats_{$cafe->id}_" . ($branchIds !== null ? md5(json_encode($branchIds)) : 'all') . '_' . now()->toDateString();
```

In `QrScanController` `recent`, change `$this->qrService->getRecentScans($cafe, 10)` to `$this->qrService->getRecentScans($cafe, 10, $this->accessibleBranchIds($request))`. In `stats`, change `$this->qrService->getScanStats($cafe)` to `$this->qrService->getScanStats($cafe, $this->accessibleBranchIds($request))`.

- [ ] **Step 5: Run the test + regression**

Run: `php artisan test --filter=BranchDataIsolationTest` then `php artisan test tests/Feature/CafeAdmin`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/QrScanService.php app/Http/Controllers/QrScanController.php tests/Feature/CafeAdmin/BranchDataIsolationTest.php
git commit -m "feat(staff-authz): branch-isolate QR scan (Phase 2 task 3)"
```

---

## Task 4: Occupancy isolation

**Files:**
- Modify: `app/Http/Controllers/OccupancyController.php` (`getCurrentBranch`, `index`, `peakTimes`, `sections`, `updateCapacity`)
- Test: `tests/Feature/CafeAdmin/BranchDataIsolationTest.php` (append)

**Interfaces:**
- Consumes: `accessibleBranchIds($request)`, `denyIfBranchInaccessible($request, int $branchId)`.

- [ ] **Step 1: Write the failing test**

Append to `BranchDataIsolationTest`:

```php
    /** @test */
    public function staff_occupancy_uses_an_assigned_branch()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        // Staff assigned ONLY to branch B (not the cafe's first branch A).
        $staff = $this->makeStaff($cafe, [$branchB->id], ['view-occupancy']);
        Sanctum::actingAs($staff);

        $res = $this->getJson('/api/v1/cafe-admin/occupancy')->assertStatus(200);
        // The resolved branch must be the staff's assigned branch B, never A.
        $this->assertEquals($branchB->id, $res->json('data.branch.id'));
    }
```

(If the occupancy payload does not expose `data.branch.id`, assert on a field that does identify the branch; confirm the actual key by inspecting `OccupancyService::getOccupancyDashboard` output during Step 2.)

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter="BranchDataIsolationTest::staff_occupancy_uses_an_assigned_branch"`
Expected: FAIL — `getCurrentBranch` returns branch A (first cafe branch), so the assertion mismatches.

- [ ] **Step 3: Make `getCurrentBranch` prefer an accessible branch**

In `app/Http/Controllers/OccupancyController.php`, replace `getCurrentBranch`:

```php
    protected function getCurrentBranch(Request $request)
    {
        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) return null;

        // For now, get the first branch or you can implement branch switching
        // In a real app, track the "current" selected branch per user session
        return $cafe->branches()->first();
    }
```

with:

```php
    protected function getCurrentBranch(Request $request)
    {
        $cafe = $this->getOwnerCafe($request);
        if (!$cafe) return null;

        // Phase 2: resolve within the acting user's accessible branches.
        // Prefer the cafe's current branch when it is accessible, else the first accessible branch.
        $accessible = $this->accessibleBranchIds($request);
        $query = $cafe->branches()->whereIn('id', $accessible);

        if ($cafe->current_branch_id && in_array($cafe->current_branch_id, $accessible, true)) {
            return $query->find($cafe->current_branch_id) ?? $query->first();
        }

        return $query->first();
    }
```

- [ ] **Step 4: Guard `updateCapacity` target branch**

`updateCapacity` also resolves via `getCurrentBranch` (now accessible-scoped). If it accepts an explicit branch/section target from the request, resolve that target's branch id and guard it. Immediately after the `$branch = $this->getCurrentBranch($request);` + null-check in `updateCapacity`, add (only if a request-supplied branch/section id can override the current branch):

```php
        if ($request->filled('branch_id') && ($deny = $this->denyIfBranchInaccessible($request, (int) $request->input('branch_id')))) {
            return $deny;
        }
```

(If `updateCapacity` takes no explicit branch target, skip this insert — the `getCurrentBranch` change already scopes it.)

- [ ] **Step 5: Run the test + regression**

Run: `php artisan test --filter=BranchDataIsolationTest` then `php artisan test tests/Feature/CafeAdmin`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/OccupancyController.php tests/Feature/CafeAdmin/BranchDataIsolationTest.php
git commit -m "feat(staff-authz): branch-isolate occupancy (Phase 2 task 4)"
```

---

## Task 5: Analytics / Dashboard isolation

**Files:**
- Modify: `app/Http/Controllers/AnalyticsController.php` (all aggregate methods + `{branchId}` guards)
- Modify: `app/Http/Controllers/DashboardController.php` (`index`, `upcomingMatches`, `recentBookings`)
- Test: `tests/Feature/CafeAdmin/BranchDataIsolationTest.php` (append)

**Interfaces:**
- Consumes: `accessibleBranchIds($request)`, `denyIfBranchInaccessible($request, int $branchId)`.

- [ ] **Step 1: Write the failing test**

Append to `BranchDataIsolationTest`:

```php
    /** @test */
    public function staff_analytics_overview_counts_only_assigned_branch()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $matchA = GameMatch::factory()->create(['branch_id' => $branchA->id]);
        $matchB = GameMatch::factory()->create(['branch_id' => $branchB->id]);
        Booking::factory()->create(['branch_id' => $branchA->id, 'match_id' => $matchA->id]);
        Booking::factory()->count(3)->create(['branch_id' => $branchB->id, 'match_id' => $matchB->id]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['view-analytics']);
        Sanctum::actingAs($staff);

        $res = $this->getJson('/api/v1/cafe-admin/analytics/overview')->assertStatus(200);
        // Only branch A's single booking must be counted, not branch B's 3.
        $this->assertEquals(1, $res->json('data.total_bookings'));
    }

    /** @test */
    public function staff_analytics_for_unassigned_branch_is_forbidden()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $staff = $this->makeStaff($cafe, [$branchA->id], ['view-analytics']);
        Sanctum::actingAs($staff);

        $this->getJson("/api/v1/cafe-admin/branches/{$branchB->id}/analytics/dashboard")
            ->assertStatus(403);
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="BranchDataIsolationTest::staff_analytics_overview_counts_only_assigned_branch"`
Expected: FAIL — `total_bookings` is 4 (all cafe branches), expected 1.

- [ ] **Step 3: Substitute the cafe-wide branch set with the accessible set**

In `app/Http/Controllers/AnalyticsController.php`, every aggregate currently derives its branch set from `$cafe->branches()->pluck('id')`. Replace **each** occurrence of the exact expression:

```php
$cafe->branches()->pluck('id')
```

with:

```php
collect($this->accessibleBranchIds($request))
```

in these methods: `overview`, `revenue`, `bookings`, `peakHours`, `customers`, `matches`, `chartData`, `topMatches`, `occupancy`, `exportReport`. (`collect(...)` preserves the `->pluck`-style collection API the surrounding code expects.)

- [ ] **Step 4: Guard the explicit `{branchId}` analytics variants**

In each method that takes `$branchId` (e.g. `overview`, `revenue`, `bookings`, `chartData`, `topMatches`, `occupancy`, `exportReport`) and the route `GET /branches/{branchId}/analytics/dashboard`, when `$branchId` is provided, guard it BEFORE building `$matchIds`. At the start of each such method, after the permission check and cafe resolution, add:

```php
        if ($branchId && ($deny = $this->denyIfBranchInaccessible($request, (int) $branchId))) {
            return $deny;
        }
```

- [ ] **Step 5: Apply the same substitution to Dashboard**

In `app/Http/Controllers/DashboardController.php` `index`, `upcomingMatches`, `recentBookings`, replace each `$cafe->branches()->pluck('id')` occurrence with `collect($this->accessibleBranchIds($request))`.

- [ ] **Step 6: Run the tests + regression**

Run: `php artisan test --filter=BranchDataIsolationTest` then `php artisan test tests/Feature/CafeAdmin`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/AnalyticsController.php app/Http/Controllers/DashboardController.php tests/Feature/CafeAdmin/BranchDataIsolationTest.php
git commit -m "feat(staff-authz): branch-isolate analytics + dashboard (Phase 2 task 5)"
```

---

## Task 6: Offers isolation (with cafe-wide offers preserved)

**Files:**
- Modify: `app/Services/OfferAdminService.php` (`list`, `getDetail`)
- Modify: `app/Http/Controllers/OfferAdminController.php` (`index`, `show`, `update`, `destroy`, `updateStatus`, `uploadImage`, branch variants)
- Test: `tests/Feature/CafeAdmin/BranchDataIsolationTest.php` (append)

**Interfaces:**
- Consumes: `accessibleBranchIds($request)`, `denyIfBranchInaccessible($request, int $branchId)`.

- [ ] **Step 1: Write the failing test**

Append to `BranchDataIsolationTest` (add `use App\Models\Offer;` to the file's imports):

```php
    /** @test */
    public function staff_offers_list_shows_assigned_branch_and_cafe_wide_only()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $offerA = Offer::factory()->create(['cafe_id' => $cafe->id, 'branch_id' => $branchA->id]);
        $offerB = Offer::factory()->create(['cafe_id' => $cafe->id, 'branch_id' => $branchB->id]);
        $offerCafeWide = Offer::factory()->create(['cafe_id' => $cafe->id, 'branch_id' => null]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['manage-offers']);
        Sanctum::actingAs($staff);

        $res = $this->getJson('/api/v1/cafe-admin/offers')->assertStatus(200);
        $ids = collect($res->json('data'))->pluck('id')->all();
        $this->assertContains($offerA->id, $ids);
        $this->assertContains($offerCafeWide->id, $ids);
        $this->assertNotContains($offerB->id, $ids);
    }

    /** @test */
    public function staff_cannot_modify_unassigned_branch_offer()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $offerB = Offer::factory()->create(['cafe_id' => $cafe->id, 'branch_id' => $branchB->id]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['manage-offers']);
        Sanctum::actingAs($staff);

        $this->getJson("/api/v1/cafe-admin/offers/{$offerB->id}")->assertStatus(403);
        $this->deleteJson("/api/v1/cafe-admin/offers/{$offerB->id}")->assertStatus(403);
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="BranchDataIsolationTest::staff_offers_list_shows_assigned_branch_and_cafe_wide_only"`
Expected: FAIL — offer B present in list.

- [ ] **Step 3: Scope `OfferAdminService::list` (keep cafe-wide offers)**

In `app/Services/OfferAdminService.php`, change `list` signature to `public function list(Cafe $cafe, ?string $status = null, ?array $branchIds = null)` and add the branch constraint after `$query = $cafe->offers()->orderBy('created_at', 'desc');`:

```php
        if ($branchIds !== null) {
            $query->where(function ($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds)
                  ->orWhereNull('branch_id'); // cafe-wide offers stay visible to all staff
            });
        }
```

- [ ] **Step 4: Pass accessible ids from `index`; guard the offer `{id}` handlers**

In `app/Http/Controllers/OfferAdminController.php` `index`, change `$this->offerService->list($cafe, $request->query('status'))` to `$this->offerService->list($cafe, $request->query('status'), $this->accessibleBranchIds($request))`.

For `show`, `update`, `destroy`, `updateStatus`, `uploadImage`, and the branch variants (`updateBranch`, `deleteBranch`, `toggleStatus`, `uploadImageBranch`): after the offer is resolved and confirmed to belong to the cafe (the existing `getDetail`/`findOrFail` + cafe check), add — guarding only branch-scoped offers (a `null` branch offer is cafe-wide and passes):

```php
        if ($offer->branch_id && ($deny = $this->denyIfBranchInaccessible($request, (int) $offer->branch_id))) {
            return $deny;
        }
```

(Use the actual resolved offer variable name in each handler; `$offer` here is illustrative.)

- [ ] **Step 5: Run the tests + regression**

Run: `php artisan test --filter=BranchDataIsolationTest` then `php artisan test tests/Feature/CafeAdmin`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/OfferAdminService.php app/Http/Controllers/OfferAdminController.php tests/Feature/CafeAdmin/BranchDataIsolationTest.php
git commit -m "feat(staff-authz): branch-isolate offers, keep cafe-wide (Phase 2 task 6)"
```

---

## Task 7: Seating isolation

**Files:**
- Modify: `app/Http/Controllers/SeatingAdminController.php` (`getOwnerSection`, `getOwnerSeat`, list + write handlers)
- Test: `tests/Feature/CafeAdmin/BranchDataIsolationTest.php` (append)

**Interfaces:**
- Consumes: `accessibleBranchIds($request)`, `denyIfBranchInaccessible($request, int $branchId)`.

- [ ] **Step 1: Write the failing test**

Append to `BranchDataIsolationTest` (add `use App\Models\SeatingSection;` to imports):

```php
    /** @test */
    public function staff_cannot_list_or_modify_unassigned_branch_sections()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $sectionB = SeatingSection::factory()->create(['branch_id' => $branchB->id]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['manage-seating']);
        Sanctum::actingAs($staff);

        // Listing sections of an unassigned branch → 403 (branch target guard).
        $this->getJson("/api/v1/cafe-admin/branches/{$branchB->id}/sections")->assertStatus(403);
        // Deleting a section on an unassigned branch → 403.
        $this->deleteJson("/api/v1/cafe-admin/sections/{$sectionB->id}")->assertStatus(403);
    }
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter="BranchDataIsolationTest::staff_cannot_list_or_modify_unassigned_branch_sections"`
Expected: FAIL — returns 200 instead of 403.

- [ ] **Step 3: Guard the branch- and section-scoped handlers**

In `app/Http/Controllers/SeatingAdminController.php`:

For handlers that take a branch `$id` and resolve via `getOwnerBranch` (`listSections`, `createSection`, `bulkCreateSections`, `listSeats`, `bulkAddSeats`, `seatingLayout`): after the `$branch = $this->getOwnerBranch($request, $id);` + null-check, add:

```php
        if ($deny = $this->denyIfBranchInaccessible($request, (int) $branch->id)) {
            return $deny;
        }
```

For handlers that resolve a section via `getOwnerSection` (`updateSection`, `deleteSection`) or a seat via `getOwnerSeat` (`updateSeat`, `deleteSeat`, `toggleAvailability`): after the resolver + null-check, add — using the resolved resource's branch id:

```php
        if ($deny = $this->denyIfBranchInaccessible($request, (int) $section->branch_id)) {
            return $deny;
        }
```

(For seat handlers, use `$seat->section->branch_id`; the resolver already eager-loads `section.branch`.)

- [ ] **Step 4: Run the test + regression**

Run: `php artisan test --filter=BranchDataIsolationTest` then `php artisan test tests/Feature/CafeAdmin`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/SeatingAdminController.php tests/Feature/CafeAdmin/BranchDataIsolationTest.php
git commit -m "feat(staff-authz): branch-isolate seating (Phase 2 task 7)"
```

---

## Final verification

- [ ] **Full suite:** `php artisan test` → PASS (no regressions).
- [ ] **Empty-set fail-closed spot check:** a staff member with a membership but zero branch assignments gets `[]` from every list and 403 from every target guard (add one such test if not already covered by a group).
- [ ] **No stray idiom:** `grep -rn "branches()->pluck('id')" app/Http/Controllers app/Services` — every remaining occurrence is intentional (owner-facing fallbacks inside services, or the cafe-scoped existence probes), not an un-narrowed staff query.

## Spec coverage (Phase 2)

- §3 mechanism (helper + two shapes) → Task 1 (helper) + all tasks.
- §4.1 Bookings → Task 1. §4.2 Matches → Task 2. §4.3 QR → Task 3. §4.4 Occupancy → Task 4.
  §4.5 Analytics/Dashboard → Task 5. §4.6 Offers (incl. cafe-wide null-branch) → Task 6.
  §4.7 Seating → Task 7.
- §5 testing (owner regression, staff narrowing, target guard, empty set, cafe-wide offers) → per-task tests + final verification.
- §3.1 response codes (403 reads+writes, silent list filter, 404 missing) → every guard/filter.
- §6 edge cases (fail-closed empty set, owner no-op, booking dual-linkage, offers null branch) → Tasks 1/6 + final verification.
