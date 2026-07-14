# Cafe Staff Authorization — Phase 1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make a staff member able to log into `/cafe-admin/*`, see their cafe and assigned branches, switch only to assigned branches, and reach only the endpoints their granted permissions allow — while owners are completely unaffected.

**Architecture:** A `CafeContextResolver` service resolves the acting cafe + accessible branches + effective permissions for owner OR staff; a `ResolvesCafeContext` trait on the base `Controller` exposes `actingCafe()`; an `EnsureCafePermission` middleware gates each route (owner bypass, `owner` token for owner-only, staff checked against their permission set). Controllers change only by swapping the cafe-resolution line and (for two endpoints) applying branch scoping.

**Tech Stack:** Laravel 12, PHP 8.2, PHPUnit 11, Spatie laravel-permission, Sanctum, SQLite in-memory for tests.

Implements **Phase 1** of `docs/superpowers/specs/2026-07-14-cafe-staff-authorization-design.md`. Phase 2 (branch-level data isolation across bookings/matches/seating/occupancy/offers/analytics/QR) is a separate follow-up plan.

## Global Constraints

- API only; Railway deploy; AWS untouched. Do **not** deploy between tasks (staff gating is only complete after Task 3).
- Roles are exactly `admin`, `manager`, `staff`. Permission catalog verbatim:
  `manage-bookings, view-bookings, manage-matches, view-analytics, manage-offers, manage-menu, manage-branches, manage-seating, manage-subscription, scan-qr, check-in-customers, view-occupancy, manage-cafe-profile, manage-staff, manage-inventory, process-payments, full-admin-access`.
- **Owner is never blocked** by permission gates; owner behavior must be byte-for-byte unchanged.
- Staff effective permissions = **union of Spatie perms (`getAllPermissions`) and the custom `branch_staff_permissions` table** (`App\Models\Permission`).
- A staff member belongs to one cafe: resolve via `staffMemberships()->accepted()->first()->cafe`.
- 403 body: `{ "success": false, "message": "You do not have permission to perform this action." }`. No-cafe: 404 `{ "success": false, "message": "No cafe found." }`.
- Tests: `tests/Feature/CafeAdmin/*`, `RefreshDatabase`, `/** @test */`, `Sanctum::actingAs`. Seed `RolesAndPermissionsSeeder` when granting/checking permissions (Spatie perms must exist). Owner factory: `User::factory()->cafeOwner()`; cafe: `Cafe::factory()->create(['owner_id'=>$owner->id])`; branch: `Branch::factory()->create(['cafe_id'=>$cafe->id])`.

---

## File Structure

- Create `app/Support/CafeContext.php` — value object `{ cafe, isOwner, accessibleBranchIds, permissions }` + helpers `can()`, `canAccessBranch()`.
- Create `app/Services/CafeContextResolver.php` — `resolve(User): ?CafeContext`.
- Create `app/Http/Controllers/Concerns/ResolvesCafeContext.php` — trait: `cafeContext(Request)`, `actingCafe(Request)`.
- Create `app/Http/Middleware/EnsureCafePermission.php` — the `cafe.permission` gate.
- Modify `app/Http/Controllers/Controller.php` — use the trait.
- Modify `bootstrap/app.php` — register `cafe.permission` alias.
- Modify `routes/api.php` — apply `cafe.permission:<perm>` per the map.
- Modify the 12 cafe-admin controllers — swap `ownedCafes()->first()` → `actingCafe($request)`.
- Modify `app/Http/Controllers/CafeAdminController.php` — branch-scope `listBranches` + `switchCurrentBranch`.
- Modify `app/Http/Controllers/StaffController.php` — remove redundant `manage-staff` checks; add delegation guardrails.
- Tests: `tests/Feature/CafeAdmin/CafeContextTest.php`, `StaffAuthorizationTest.php` (new).

---

## Task 1: CafeContext + resolver + trait

**Files:**
- Create: `app/Support/CafeContext.php`, `app/Services/CafeContextResolver.php`, `app/Http/Controllers/Concerns/ResolvesCafeContext.php`
- Modify: `app/Http/Controllers/Controller.php`
- Test: `tests/Feature/CafeAdmin/CafeContextTest.php`

**Interfaces:**
- Produces:
  - `App\Support\CafeContext` with public readonly `Cafe $cafe`, `bool $isOwner`, `array $accessibleBranchIds` (int[]), `array $permissions` (string[]); methods `can(string $permission): bool`, `canAccessBranch(int $branchId): bool`.
  - `App\Services\CafeContextResolver::resolve(App\Models\User $user): ?CafeContext`.
  - Trait `App\Http\Controllers\Concerns\ResolvesCafeContext` with `protected function cafeContext(Request $request): ?CafeContext` and `protected function actingCafe(Request $request): ?\App\Models\Cafe`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CafeAdmin/CafeContextTest.php`:

```php
<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Branch;
use App\Models\Cafe;
use App\Models\User;
use App\Services\CafeContextResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CafeContextTest extends TestCase
{
    use RefreshDatabase;

    private function resolver(): CafeContextResolver
    {
        return app(CafeContextResolver::class);
    }

    /** @test */
    public function it_resolves_owner_context_with_all_branches()
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $b1 = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $b2 = Branch::factory()->create(['cafe_id' => $cafe->id]);

        $ctx = $this->resolver()->resolve($owner);

        $this->assertNotNull($ctx);
        $this->assertTrue($ctx->isOwner);
        $this->assertEquals($cafe->id, $ctx->cafe->id);
        $this->assertEqualsCanonicalizing([$b1->id, $b2->id], $ctx->accessibleBranchIds);
        $this->assertTrue($ctx->can('manage-matches')); // owner can anything
    }

    /** @test */
    public function it_resolves_staff_context_with_assigned_branches_and_permissions()
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $b1 = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $b2 = Branch::factory()->create(['cafe_id' => $cafe->id]);

        $staff = User::factory()->staff()->create();
        $staff->staffMemberships()->create([
            'cafe_id' => $cafe->id, 'role' => 'manager', 'invitation_status' => 'accepted',
        ]);
        // assigned to b1 only
        $staff->branchAssignments()->attach($b1->id, ['role' => 'manager']);
        $staff->givePermissionTo('manage-matches');

        $ctx = $this->resolver()->resolve($staff);

        $this->assertNotNull($ctx);
        $this->assertFalse($ctx->isOwner);
        $this->assertEquals($cafe->id, $ctx->cafe->id);
        $this->assertEquals([$b1->id], $ctx->accessibleBranchIds);
        $this->assertTrue($ctx->can('manage-matches'));
        $this->assertFalse($ctx->can('manage-offers'));
        $this->assertTrue($ctx->canAccessBranch($b1->id));
        $this->assertFalse($ctx->canAccessBranch($b2->id));
    }

    /** @test */
    public function it_returns_null_when_user_has_no_cafe()
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $nobody = User::factory()->create();
        $this->assertNull($this->resolver()->resolve($nobody));
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=CafeContextTest`
Expected: FAIL — `Class "App\Services\CafeContextResolver" not found`.

- [ ] **Step 3: Create the `CafeContext` value object**

Create `app/Support/CafeContext.php`:

```php
<?php

namespace App\Support;

use App\Models\Cafe;

class CafeContext
{
    /**
     * @param int[] $accessibleBranchIds
     * @param string[] $permissions
     */
    public function __construct(
        public readonly Cafe $cafe,
        public readonly bool $isOwner,
        public readonly array $accessibleBranchIds,
        public readonly array $permissions,
    ) {}

    public function can(string $permission): bool
    {
        return $this->isOwner || in_array($permission, $this->permissions, true);
    }

    public function canAccessBranch(int $branchId): bool
    {
        return in_array($branchId, $this->accessibleBranchIds, true);
    }
}
```

- [ ] **Step 4: Create the resolver**

Create `app/Services/CafeContextResolver.php`:

```php
<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use App\Support\CafeContext;

class CafeContextResolver
{
    public function resolve(User $user): ?CafeContext
    {
        // Owner takes precedence.
        $cafe = $user->ownedCafes()->first();
        $isOwner = $cafe !== null;

        if (!$cafe) {
            $membership = $user->staffMemberships()->accepted()->first();
            $cafe = $membership?->cafe;
        }

        if (!$cafe) {
            return null;
        }

        $cafeBranchIds = $cafe->branches()->pluck('id')->all();

        if ($isOwner) {
            return new CafeContext($cafe, true, $cafeBranchIds, []);
        }

        // Staff: assigned branches within this cafe.
        $branchIds = $user->branchAssignments()
            ->whereIn('branches.id', $cafeBranchIds)
            ->pluck('branches.id')
            ->all();

        // Effective permissions = Spatie ∪ custom branch_staff_permissions table.
        $spatie = $user->getAllPermissions()->pluck('name')->all();
        $custom = Permission::where('user_id', $user->id)->pluck('permission')->all();
        $permissions = array_values(array_unique(array_merge($spatie, $custom)));

        return new CafeContext($cafe, false, $branchIds, $permissions);
    }
}
```

- [ ] **Step 5: Create the trait**

Create `app/Http/Controllers/Concerns/ResolvesCafeContext.php`:

```php
<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Cafe;
use App\Services\CafeContextResolver;
use App\Support\CafeContext;
use Illuminate\Http\Request;

trait ResolvesCafeContext
{
    protected function cafeContext(Request $request): ?CafeContext
    {
        if ($request->attributes->has('cafe_context')) {
            return $request->attributes->get('cafe_context');
        }

        $ctx = $request->user()
            ? app(CafeContextResolver::class)->resolve($request->user())
            : null;

        $request->attributes->set('cafe_context', $ctx);

        return $ctx;
    }

    protected function actingCafe(Request $request): ?Cafe
    {
        return $this->cafeContext($request)?->cafe;
    }
}
```

- [ ] **Step 6: Wire the trait into the base controller**

Modify `app/Http/Controllers/Controller.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesCafeContext;
use App\Traits\ApiResponse;

abstract class Controller
{
    use ApiResponse;
    use ResolvesCafeContext;
}
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `php artisan test --filter=CafeContextTest`
Expected: PASS (3 tests).

- [ ] **Step 8: Commit**

```bash
git add app/Support/CafeContext.php app/Services/CafeContextResolver.php app/Http/Controllers/Concerns/ResolvesCafeContext.php app/Http/Controllers/Controller.php tests/Feature/CafeAdmin/CafeContextTest.php
git commit -m "feat(staff-authz): add CafeContext resolver + trait"
```

---

## Task 2: Swap cafe resolution to actingCafe() across cafe-admin controllers

**Files:**
- Modify: `app/Http/Controllers/CafeAdminController.php`, `BookingAdminController.php`, `MatchAdminController.php`, `SeatingAdminController.php`, `OccupancyController.php`, `OfferAdminController.php`, `QrScanController.php`, `AnalyticsController.php`, `DashboardController.php`, `SubscriptionController.php`, `BillingController.php`, `StaffController.php`
- Test: `tests/Feature/CafeAdmin/StaffAuthorizationTest.php` (new)

**Interfaces:**
- Consumes: `actingCafe(Request)` from Task 1.
- Produces: staff now resolve their cafe on every cafe-admin endpoint (gating added in Task 3).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/CafeAdmin/StaffAuthorizationTest.php`:

```php
<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Branch;
use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Owner + cafe + one branch + an ACTIVE subscription that allows staff
     * (POST /staff enforces canAddStaff).
     *
     * @return array{0: User, 1: Cafe, 2: Branch}
     */
    protected function cafeWithOwner(): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $plan = SubscriptionPlan::factory()->create(['max_staff_members' => 10, 'is_active' => true]);
        CafeSubscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addMonth(),
        ]);
        return [$owner, $cafe, $branch];
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
    public function staff_can_see_their_cafe()
    {
        [$owner, $cafe, $branch] = $this->cafeWithOwner();
        $staff = $this->makeStaff($cafe, [$branch->id], []);
        Sanctum::actingAs($staff);

        $this->getJson('/api/v1/cafe-admin/cafe')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $cafe->id);
    }

    /** @test */
    public function owner_can_still_see_their_cafe()
    {
        [$owner, $cafe] = $this->cafeWithOwner();
        Sanctum::actingAs($owner);

        $this->getJson('/api/v1/cafe-admin/cafe')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $cafe->id);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter="StaffAuthorizationTest::staff_can_see_their_cafe"`
Expected: FAIL — staff gets 404 "No cafe found for this owner" (controller still uses `ownedCafes()`).

- [ ] **Step 3: Replace the resolution idiom in every cafe-admin controller**

In each of the 12 controllers listed under **Files**, replace every occurrence of:

```php
$request->user()->ownedCafes()->first()
```

with:

```php
$this->actingCafe($request)
```

Run this to find remaining occurrences after editing (must print nothing):

```bash
grep -rn "ownedCafes()->first()" app/Http/Controllers
```

Leave the existing `if (!$cafe) { ... 404 ... }` guards as-is. Do **not** change any other logic in these controllers in this task.

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php artisan test --filter=StaffAuthorizationTest`
Expected: PASS (2 tests) — staff and owner both see their cafe.

- [ ] **Step 5: Run the full cafe-admin suite for owner regressions**

Run: `php artisan test tests/Feature/CafeAdmin`
Expected: PASS (all existing tests still green — owners behave identically).

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers tests/Feature/CafeAdmin/StaffAuthorizationTest.php
git commit -m "feat(staff-authz): resolve acting cafe for owner or staff in cafe-admin controllers"
```

---

## Task 3: cafe.permission middleware + route gating

**Files:**
- Create: `app/Http/Middleware/EnsureCafePermission.php`
- Modify: `bootstrap/app.php` (alias), `routes/api.php` (apply middleware), `app/Http/Controllers/StaffController.php` (remove redundant manage-staff checks)
- Test: `tests/Feature/CafeAdmin/StaffAuthorizationTest.php` (append)

**Interfaces:**
- Consumes: `CafeContextResolver` (Task 1).
- Produces: alias `cafe.permission:<perm>`; token `owner` = owner-only. Stashes the resolved context on the request under `cafe_context`.

- [ ] **Step 1: Write the failing tests**

Append to `StaffAuthorizationTest`:

```php
/** @test */
public function staff_with_permission_can_hit_gated_endpoint()
{
    [$owner, $cafe, $branch] = $this->cafeWithOwner();
    $staff = $this->makeStaff($cafe, [$branch->id], ['view-analytics']);
    Sanctum::actingAs($staff);

    $this->getJson('/api/v1/cafe-admin/analytics/overview')->assertStatus(200);
}

/** @test */
public function staff_without_permission_is_forbidden()
{
    [$owner, $cafe, $branch] = $this->cafeWithOwner();
    $staff = $this->makeStaff($cafe, [$branch->id], []); // no view-analytics
    Sanctum::actingAs($staff);

    $this->getJson('/api/v1/cafe-admin/analytics/overview')
        ->assertStatus(403)
        ->assertJsonPath('message', 'You do not have permission to perform this action.');
}

/** @test */
public function staff_cannot_access_owner_only_subscription()
{
    [$owner, $cafe, $branch] = $this->cafeWithOwner();
    $staff = $this->makeStaff($cafe, [$branch->id], ['manage-subscription']);
    Sanctum::actingAs($staff);

    $this->getJson('/api/v1/cafe-admin/subscription')->assertStatus(403);
}

/** @test */
public function owner_bypasses_permission_gates()
{
    [$owner, $cafe, $branch] = $this->cafeWithOwner();
    Sanctum::actingAs($owner);

    $this->getJson('/api/v1/cafe-admin/analytics/overview')->assertStatus(200);
    $this->getJson('/api/v1/cafe-admin/subscription')->assertStatus(200);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="StaffAuthorizationTest::staff_without_permission_is_forbidden"`
Expected: FAIL — returns 200 (no gating yet), expected 403.

- [ ] **Step 3: Create the middleware**

Create `app/Http/Middleware/EnsureCafePermission.php`:

```php
<?php

namespace App\Http\Middleware;

use App\Services\CafeContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCafePermission
{
    public function __construct(private CafeContextResolver $resolver) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $ctx = $user ? $this->resolver->resolve($user) : null;

        if (!$ctx) {
            return response()->json(['success' => false, 'message' => 'No cafe found.'], 404);
        }

        // Reuse downstream (controllers read the same context).
        $request->attributes->set('cafe_context', $ctx);

        $allowed = $permission === 'owner' ? $ctx->isOwner : $ctx->can($permission);

        if ($allowed) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }
}
```

- [ ] **Step 4: Register the alias**

Modify `bootstrap/app.php` — add to the `$middleware->alias([...])` block:

```php
            'cafe.permission' => \App\Http\Middleware\EnsureCafePermission::class,
```

- [ ] **Step 5: Apply the gate to each cafe-admin route**

In `routes/api.php`, inside the `prefix('cafe-admin')` group, append `->middleware('cafe.permission:<perm>')` to each route per this map. Routes not listed (the **membership** bootstrap routes: `GET /cafe`, `GET /onboarding-status`, `GET /current-branch`, `PUT /current-branch`, `GET /roles-permissions`, and the branch/match/offer/seating **reads**) get **no** `cafe.permission` middleware.

| Route(s) | `cafe.permission:` |
|---|---|
| `POST /cafe` | `owner` |
| `PUT /cafe`, `POST /cafe/logo` | `manage-cafe-profile` |
| `POST /branches`, `PUT /branches/{id}`, `DELETE /branches/{id}`, `PUT /branches/{id}/hours`, `POST /branches/{id}/amenities/bulk`, `POST /branches/{id}/amenities`, `DELETE /amenities/{id}`, `PUT /branches/{id}/status` | `manage-branches` |
| `POST /branches/{id}/sections`, `POST /branches/{id}/sections/bulk`, `PUT /sections/{id}`, `DELETE /sections/{id}`, `POST /sections/{id}/seats`, `PUT /seats/{id}`, `DELETE /seats/{id}` | `manage-seating` |
| `POST /matches`, `PUT /matches/{id}`, `DELETE /matches/{id}`, `POST /matches/{id}/publish`, `PUT /matches/{id}/score`, `PUT /matches/{id}/status`, `POST /matches/{id}/reminder` | `manage-matches` |
| `GET /bookings`, `GET /bookings/{id}`, `GET /bookings/today-summary` | `view-bookings` |
| `POST /bookings/{id}/check-in` | `check-in-customers` |
| `POST /bookings/{id}/cancel` | `manage-bookings` |
| `POST /scan-qr`, `POST /scan-qr/upload`, `GET /scan-qr/recent`, `GET /scan-qr/stats` | `scan-qr` |
| `GET /occupancy`, `GET /occupancy/peak-times`, `GET /occupancy/sections` | `view-occupancy` |
| `PUT /occupancy/capacity` | `manage-seating` |
| `POST /offers`, `PUT /offers/{id}`, `DELETE /offers/{id}`, `POST /offers/{id}/upload-image`, `PUT /offers/{id}/status` | `manage-offers` |
| `GET /dashboard`, `GET /dashboard/upcoming-matches`, `GET /dashboard/recent-bookings`, `GET /analytics/*` | `view-analytics` |
| `GET /staff`, `GET /staff/{id}`, `POST /staff`, `PUT /staff/{id}`, `DELETE /staff/{id}`, `POST /staff/{id}/resend-invite` | `manage-staff` |
| `GET /subscription`, `POST /subscription/upgrade`, `POST /subscription/cancel`, `PUT /subscription/auto-renew`, `GET /subscription/usage`, `GET /billing`, `GET /billing/summary`, `GET /billing/export`, `PUT /billing/payment-method` | `owner` |

Worked examples (before → after):

```php
// before
Route::get('/analytics/overview', [\App\Http\Controllers\AnalyticsController::class, 'overview'])->name('analytics.overview');
// after
Route::get('/analytics/overview', [\App\Http\Controllers\AnalyticsController::class, 'overview'])->name('analytics.overview')->middleware('cafe.permission:view-analytics');

// before
Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'current'])->name('subscription.current');
// after
Route::get('/subscription', [\App\Http\Controllers\SubscriptionController::class, 'current'])->name('subscription.current')->middleware('cafe.permission:owner');
```

- [ ] **Step 6: Remove the now-redundant manage-staff checks in StaffController**

The `cafe.permission:manage-staff` middleware now gates all `/staff` routes, and it reads the correct (Spatie ∪ custom) permission set — whereas the in-controller `hasPermissionTo('manage-staff')` uses `User::can()` which does **not** see Spatie-granted staff perms and would wrongly 403 legitimate staff. In `app/Http/Controllers/StaffController.php`, delete this guard block from `index`, `store`, `show`, `update`, `destroy`, and `resendInvite` (6 methods):

```php
        // Check permission
        if (!$request->user()->hasPermissionTo('manage-staff')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage staff.',
            ], 403);
        }
```

- [ ] **Step 7: Run the tests to verify they pass**

Run: `php artisan test --filter=StaffAuthorizationTest`
Expected: PASS. And run the full suite for regressions:
Run: `php artisan test tests/Feature/CafeAdmin`
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Middleware/EnsureCafePermission.php bootstrap/app.php routes/api.php app/Http/Controllers/StaffController.php tests/Feature/CafeAdmin/StaffAuthorizationTest.php
git commit -m "feat(staff-authz): permission-gate cafe-admin routes (owner bypass, owner-only, staff by permission)"
```

---

## Task 4: Phase-1 branch restriction (switch + list)

**Files:**
- Modify: `app/Http/Controllers/CafeAdminController.php` (`switchCurrentBranch`, `listBranches`)
- Test: `tests/Feature/CafeAdmin/StaffAuthorizationTest.php` (append)

**Interfaces:**
- Consumes: `cafeContext(Request)` (returns `CafeContext` with `accessibleBranchIds`, `canAccessBranch()`).

- [ ] **Step 1: Write the failing tests**

Append to `StaffAuthorizationTest`:

```php
/** @test */
public function staff_can_switch_to_assigned_branch_but_not_others()
{
    [$owner, $cafe, $assigned] = $this->cafeWithOwner();
    $other = Branch::factory()->create(['cafe_id' => $cafe->id]);
    $staff = $this->makeStaff($cafe, [$assigned->id], ['manage-branches']);
    Sanctum::actingAs($staff);

    $this->putJson('/api/v1/cafe-admin/current-branch', ['branch_id' => $assigned->id])
        ->assertStatus(200);

    $this->putJson('/api/v1/cafe-admin/current-branch', ['branch_id' => $other->id])
        ->assertStatus(403);
}

/** @test */
public function list_branches_returns_only_assigned_for_staff()
{
    [$owner, $cafe, $assigned] = $this->cafeWithOwner();
    $other = Branch::factory()->create(['cafe_id' => $cafe->id]);
    $staff = $this->makeStaff($cafe, [$assigned->id], []);
    Sanctum::actingAs($staff);

    $res = $this->getJson('/api/v1/cafe-admin/branches')->assertStatus(200);
    $ids = collect($res->json('data'))->pluck('id')->all();
    $this->assertContains($assigned->id, $ids);
    $this->assertNotContains($other->id, $ids);
}

/** @test */
public function list_branches_returns_all_for_owner()
{
    [$owner, $cafe, $b1] = $this->cafeWithOwner();
    $b2 = Branch::factory()->create(['cafe_id' => $cafe->id]);
    Sanctum::actingAs($owner);

    $res = $this->getJson('/api/v1/cafe-admin/branches')->assertStatus(200);
    $ids = collect($res->json('data'))->pluck('id')->all();
    $this->assertEqualsCanonicalizing([$b1->id, $b2->id], $ids);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="StaffAuthorizationTest::staff_can_switch_to_assigned_branch_but_not_others"`
Expected: FAIL — switching to `$other` returns 200 (no restriction yet), expected 403.

- [ ] **Step 3: Restrict `switchCurrentBranch` to accessible branches**

In `app/Http/Controllers/CafeAdminController.php` `switchCurrentBranch`, immediately after the existing branch-belongs-to-cafe lookup that sets `$branch` (the block returning 404 "Branch not found or does not belong to your cafe"), add:

```php
        if (!$this->cafeContext($request)->canAccessBranch((int) $branch->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }
```

(Owner's `accessibleBranchIds` = all cafe branches, so this never blocks an owner.)

- [ ] **Step 4: Restrict `listBranches` to accessible branches**

In `listBranches`, change the branches query to constrain by the accessible set. Replace:

```php
        $branches = $cafe->branches()
            ->withCount([
```

with:

```php
        $accessibleBranchIds = $this->cafeContext($request)->accessibleBranchIds;
        $branches = $cafe->branches()
            ->whereIn('id', $accessibleBranchIds)
            ->withCount([
```

(Owner's accessible set = all cafe branches, so the result is unchanged for owners.)

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=StaffAuthorizationTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/CafeAdminController.php tests/Feature/CafeAdmin/StaffAuthorizationTest.php
git commit -m "feat(staff-authz): restrict branch switch + list to accessible branches"
```

---

## Task 5: Staff-management delegation guardrails

**Files:**
- Modify: `app/Http/Controllers/StaffController.php` (`store`, `update`, `destroy`)
- Test: `tests/Feature/CafeAdmin/StaffAuthorizationTest.php` (append)

**Interfaces:**
- Consumes: `cafeContext(Request)` → `{ isOwner, permissions, accessibleBranchIds }`.
- Rule: owner is unrestricted; a non-owner with `manage-staff` is bounded per spec §5.

- [ ] **Step 1: Write the failing tests**

Append to `StaffAuthorizationTest`:

```php
/** @test */
public function delegated_staff_cannot_create_admin_or_escalate()
{
    [$owner, $cafe, $branch] = $this->cafeWithOwner();
    $manager = $this->makeStaff($cafe, [$branch->id], ['manage-staff', 'manage-bookings']);
    Sanctum::actingAs($manager);

    // cannot create an admin
    $this->postJson('/api/v1/cafe-admin/staff', [
        'name' => 'X', 'email' => 'x1@example.com', 'password' => 'secret123',
        'role' => 'admin', 'branch_ids' => [$branch->id],
    ])->assertStatus(422);

    // cannot grant a permission they don't hold (manage-offers)
    $this->postJson('/api/v1/cafe-admin/staff', [
        'name' => 'Y', 'email' => 'y1@example.com', 'password' => 'secret123',
        'role' => 'staff', 'permissions' => ['manage-offers'], 'branch_ids' => [$branch->id],
    ])->assertStatus(422);

    // cannot assign a branch they aren't on
    $other = Branch::factory()->create(['cafe_id' => $cafe->id]);
    $this->postJson('/api/v1/cafe-admin/staff', [
        'name' => 'Z', 'email' => 'z1@example.com', 'password' => 'secret123',
        'role' => 'staff', 'branch_ids' => [$other->id],
    ])->assertStatus(422);
}

/** @test */
public function delegated_staff_can_create_permitted_staff()
{
    [$owner, $cafe, $branch] = $this->cafeWithOwner();
    $manager = $this->makeStaff($cafe, [$branch->id], ['manage-staff', 'manage-bookings']);
    Sanctum::actingAs($manager);

    $this->postJson('/api/v1/cafe-admin/staff', [
        'name' => 'OK', 'email' => 'ok1@example.com', 'password' => 'secret123',
        'role' => 'staff', 'permissions' => ['manage-bookings'], 'branch_ids' => [$branch->id],
    ])->assertStatus(201);
}

/** @test */
public function owner_is_not_restricted_by_guardrails()
{
    [$owner, $cafe, $branch] = $this->cafeWithOwner();
    Sanctum::actingAs($owner);

    $this->postJson('/api/v1/cafe-admin/staff', [
        'name' => 'Admin', 'email' => 'admin1@example.com', 'password' => 'secret123',
        'role' => 'admin', 'permissions' => ['manage-offers'], 'branch_ids' => [$branch->id],
    ])->assertStatus(201);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test --filter="StaffAuthorizationTest::delegated_staff_cannot_create_admin_or_escalate"`
Expected: FAIL — an admin is created (201) instead of 422.

- [ ] **Step 3: Add a guardrail helper + apply it in `store` and `update`**

In `app/Http/Controllers/StaffController.php`, add this private method:

```php
    /**
     * Enforce delegation guardrails for a non-owner acting on staff.
     * Returns a JsonResponse to short-circuit with, or null to proceed.
     */
    private function guardStaffDelegation(Request $request, array $data): ?\Illuminate\Http\JsonResponse
    {
        $ctx = $this->cafeContext($request);
        if ($ctx === null || $ctx->isOwner) {
            return null; // owner unrestricted
        }

        // 1) No admin creation/elevation.
        if (($data['role'] ?? null) === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You cannot assign the admin role.',
            ], 422);
        }

        // 2) No granting permissions the actor does not hold.
        $requested = $data['permissions'] ?? [];
        $escalated = array_values(array_diff($requested, $ctx->permissions));
        if (!empty($escalated)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot grant permissions you do not have.',
                'errors' => ['permissions' => $escalated],
            ], 422);
        }

        // 3) Branch bounds.
        $branchIds = $data['branch_ids'] ?? [];
        $outside = array_values(array_diff($branchIds, $ctx->accessibleBranchIds));
        if (!empty($outside)) {
            return response()->json([
                'success' => false,
                'message' => 'You can only assign your own branches.',
                'errors' => ['branch_ids' => $outside],
            ], 422);
        }

        return null;
    }
```

In `store()`, immediately after the existing branch-ownership guard (before `canAddStaff`), add:

```php
        if ($guard = $this->guardStaffDelegation($request, $request->only(['role', 'permissions', 'branch_ids']))) {
            return $guard;
        }
```

In `update()`, immediately after the branch-ownership guard (before the `try`), add:

```php
        if ($guard = $this->guardStaffDelegation($request, $request->only(['role', 'permissions', 'branch_ids']))) {
            return $guard;
        }
```

- [ ] **Step 4: Block a non-owner from modifying/removing an admin (`update` + `destroy`)**

In both `update()` and `destroy()`, immediately after the `$staffMember` existence check (`if (!$staffMember) { ...404... }`), add:

```php
        $ctx = $this->cafeContext($request);
        if ($ctx && !$ctx->isOwner && $staffMember->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }
```

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php artisan test --filter=StaffAuthorizationTest`
Expected: PASS.

- [ ] **Step 6: Run the full staff + cafe-admin suites**

Run: `php artisan test tests/Feature/CafeAdmin/StaffManagementTest.php tests/Feature/CafeAdmin/StaffTest.php tests/Feature/CafeAdmin/StaffAuthorizationTest.php tests/Feature/CafeAdmin/CafeContextTest.php`
Expected: PASS (owners + earlier staff features unaffected).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/StaffController.php tests/Feature/CafeAdmin/StaffAuthorizationTest.php
git commit -m "feat(staff-authz): staff-management delegation guardrails"
```

---

## Final verification

- [ ] Full suite: `php artisan test`
  Expected: PASS (no regressions).
- [ ] Confirm no stray old idiom: `grep -rn "ownedCafes()->first()" app/Http/Controllers` prints nothing.

## Spec coverage (Phase 1)

- §3.1 resolver/trait → Task 1. §3.2 middleware → Task 3. §3 "minimize controller changes" → Tasks 2–5 (only resolution swap + two scoping edits + guardrails).
- §4 authorization map → Task 3 (route table). Owner-only subscription/billing → Task 3.
- §5 staff-mgmt guardrails → Task 5.
- §6.1 Phase-1 branch rules (switch + list) → Task 4.
- §7 error shapes → Tasks 3–5. §8 tests → each task. §9 Phase 1 acceptance criteria → Tasks 1–4 collectively.
- Permission-store wrinkle (Spatie ∪ custom) → Task 1 resolver + Task 3 removing redundant `can()` checks.

**Out of scope (Phase 2, separate plan):** branch-level data filtering in bookings/matches/seating/occupancy/offers/analytics/QR (§6.2), and branch-target guards on branch-scoped `{id}` read/write routes.
