# Cafe Staff Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a cafe owner add a staff member through `POST /api/v1/cafe-admin/staff` by setting the staff member's email + password directly and assigning one or more branches plus a role and permissions.

**Architecture:** Extend the existing `StaffController` + `StaffService` (Approach 1 from the spec). Reuse the existing `branch_staff` pivot for branch assignments via a new `User::branchAssignments()` relationship. Keep permissions on the user via the existing Spatie sync. No new tables, no new routes, no email invitation for this flow.

**Tech Stack:** Laravel 12, PHP 8.x, PHPUnit 11, Spatie laravel-permission, Sanctum, MySQL.

## Global Constraints

- API only. No UI, no changes to the platform Livewire dashboard.
- Deploy target is Railway only. AWS/tab3s.com is untouched.
- Roles are exactly `admin`, `manager`, `staff`.
- Permission catalog (the only allowed `permissions.*` values), copied verbatim from the existing validator:
  `manage-bookings, view-bookings, manage-matches, view-analytics, manage-offers, manage-menu, manage-branches, manage-seating, scan-qr, check-in-customers, view-occupancy, manage-staff`
- `password` minimum length 8. `password` is never returned in any response.
- `email` for a new staff member must be unique in `users` (brand-new account).
- Every `branch_id` must belong to the authenticating owner's cafe.
- Follow existing test style: PHPUnit classes under `tests/Feature/CafeAdmin/`, `RefreshDatabase`, `/** @test */` annotation with snake_case method names, `Sanctum::actingAs($owner)`. The owner is `User::factory()->cafeOwner()` (its `role` column grants `manage-staff` via `User::can()`).
- Because `StaffService::syncPermissions()` calls Spatie's `givePermissionTo()`, tests that create staff MUST seed Spatie permissions first with `$this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class)`.

---

## File Structure

- `app/Models/User.php` — add `branchAssignments()` belongsToMany relationship over `branch_staff`.
- `app/Services/StaffService.php` — rewrite `inviteStaff()` (direct credentials + branch sync, no email); extend `updateStaff()` (branch sync + password); extend `removeStaff()` (detach branches); extend `getStaffDetail()` (return branches).
- `app/Http/Controllers/StaffController.php` — extend `store()` and `update()` validation + branch-ownership guard + pass new fields through.
- `app/Http/Resources/StaffResource.php` — output `branches`.
- `tests/Feature/CafeAdmin/StaffManagementTest.php` — new feature test file for this feature (keeps the existing `StaffTest.php` untouched).

---

## Task 1: Add staff with branch + owner-set credentials

**Files:**
- Modify: `app/Models/User.php` (add relationship)
- Modify: `app/Services/StaffService.php:82-133` (`inviteStaff`)
- Modify: `app/Http/Controllers/StaffController.php:66-132` (`store`)
- Test: `tests/Feature/CafeAdmin/StaffManagementTest.php` (new)

**Interfaces:**
- Consumes: `Cafe::branches()` (HasMany), `User::factory()->cafeOwner()`, `Cafe::factory()`, `Branch::factory()`.
- Produces:
  - `User::branchAssignments(): BelongsToMany` — pivot table `branch_staff`, withPivot `role`.
  - `StaffService::inviteStaff(Cafe $cafe, User $invitedBy, array $data): StaffMember` where `$data` keys are `name, email, password, role, permissions?, branch_ids`.

- [ ] **Step 1: Write the failing happy-path test**

Create `tests/Feature/CafeAdmin/StaffManagementTest.php`:

```php
<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Branch;
use App\Models\Cafe;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        return User::factory()->cafeOwner()->create();
    }

    /** @test */
    public function owner_adds_staff_with_branches_and_credentials()
    {
        Notification::fake();
        $owner = $this->owner();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $b1 = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $b2 = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/cafe-admin/staff', [
            'name' => 'Sara',
            'email' => 'sara@example.com',
            'password' => 'secret123',
            'role' => 'manager',
            'permissions' => ['manage-bookings', 'view-analytics'],
            'branch_ids' => [$b1->id, $b2->id],
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['role' => 'manager', 'invitation_status' => 'accepted'],
            ]);

        // account created active, password hashed, never returned
        $user = User::where('email', 'sara@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('secret123', $user->password));
        $this->assertStringNotContainsString('secret123', $response->getContent());

        $this->assertDatabaseHas('staff_members', [
            'cafe_id' => $cafe->id,
            'user_id' => $user->id,
            'role' => 'manager',
            'invitation_status' => 'accepted',
        ]);
        $this->assertDatabaseHas('branch_staff', ['branch_id' => $b1->id, 'user_id' => $user->id]);
        $this->assertDatabaseHas('branch_staff', ['branch_id' => $b2->id, 'user_id' => $user->id]);

        // no invitation email/notification for this flow
        Notification::assertNothingSent();
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=owner_adds_staff_with_branches_and_credentials`
Expected: FAIL — current `store()` rejects (no `branch_ids`/`password` handling) or `branch_staff` rows missing.

- [ ] **Step 3: Add the `branchAssignments` relationship to `User`**

In `app/Models/User.php`, add the import near the other relation imports at the top:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
```

Add this method alongside the other relationships (e.g. after `staffMemberships()`):

```php
/**
 * Branches this user is assigned to as staff (via branch_staff pivot).
 */
public function branchAssignments(): BelongsToMany
{
    return $this->belongsToMany(Branch::class, 'branch_staff')
        ->withPivot('role')
        ->withTimestamps();
}
```

(`Branch` is in the same `App\Models` namespace, so no extra import is needed for it.)

- [ ] **Step 4: Rewrite `StaffService::inviteStaff`**

Replace the body of `inviteStaff()` (`app/Services/StaffService.php:82-133`) with:

```php
public function inviteStaff(Cafe $cafe, User $invitedBy, array $data): StaffMember
{
    return DB::transaction(function () use ($cafe, $invitedBy, $data) {
        // Owner is setting a password, so the email must be a brand-new account.
        if (User::where('email', $data['email'])->exists()) {
            throw new \Exception('This email is already in use.');
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'staff',
            'is_active' => true,
        ]);

        $staffMember = StaffMember::create([
            'cafe_id' => $cafe->id,
            'user_id' => $user->id,
            'role' => $data['role'],
            'invited_by' => $invitedBy->id,
            'invitation_status' => 'accepted',
        ]);

        // Permissions (global via Spatie) — same across all the staff member's branches.
        $permissions = $data['permissions'] ?? $this->getDefaultPermissionsByRole($data['role']);
        $this->syncPermissions($user, $permissions);

        // Branch assignments (idempotent under unique(branch_id, user_id)).
        $syncData = [];
        foreach (($data['branch_ids'] ?? []) as $branchId) {
            $syncData[$branchId] = ['role' => $data['role']];
        }
        $user->branchAssignments()->sync($syncData);

        return $staffMember->load(['user', 'invitedBy']);
    });
}
```

This removes the signed-URL generation and the `StaffInvitationNotification` send for this flow. Leave the `use` statements for `URL` and `StaffInvitationNotification` in place — `resendInvite()` still uses them.

- [ ] **Step 5: Extend `StaffController::store` validation + ownership guard + pass-through**

In `app/Http/Controllers/StaffController.php`, replace the validator array in `store()` (lines 76-82) with:

```php
$validator = Validator::make($request->all(), [
    'name' => 'required|string|max:255',
    'email' => 'required|email|max:255|unique:users,email',
    'password' => 'required|string|min:8',
    'role' => 'required|in:admin,manager,staff',
    'permissions' => 'nullable|array',
    'permissions.*' => 'string|in:manage-bookings,view-bookings,manage-matches,view-analytics,manage-offers,manage-menu,manage-branches,manage-seating,scan-qr,check-in-customers,view-occupancy,manage-staff',
    'branch_ids' => 'required|array|min:1',
    'branch_ids.*' => 'integer|exists:branches,id',
]);
```

Immediately after the `$cafe` existence check (after line 99, before the subscription enforcement block), add the branch-ownership guard:

```php
$ownedBranchIds = $cafe->branches()->pluck('id')->all();
$invalidBranches = array_diff($request->input('branch_ids', []), $ownedBranchIds);
if (!empty($invalidBranches)) {
    return response()->json([
        'success' => false,
        'message' => 'One or more branches do not belong to your cafe.',
    ], 422);
}
```

Change the `inviteStaff` call (lines 113-117) to pass the new fields and update the success message (line 123):

```php
$staffMember = $this->staffService->inviteStaff(
    $cafe,
    $request->user(),
    $request->only(['name', 'email', 'password', 'role', 'permissions', 'branch_ids'])
);

$staffDetail = $this->staffService->getStaffDetail($staffMember);

return response()->json([
    'success' => true,
    'message' => 'Staff member added successfully.',
    'data' => new StaffResource($staffDetail),
], 201);
```

- [ ] **Step 6: Run the happy-path test to verify it passes**

Run: `php artisan test --filter=owner_adds_staff_with_branches_and_credentials`
Expected: PASS.

- [ ] **Step 7: Add the guard tests (duplicate email, foreign branch)**

Append to `StaffManagementTest`:

```php
/** @test */
public function it_rejects_duplicate_email()
{
    $owner = $this->owner();
    $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
    User::factory()->create(['email' => 'taken@example.com']);
    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/cafe-admin/staff', [
        'name' => 'Sara',
        'email' => 'taken@example.com',
        'password' => 'secret123',
        'role' => 'staff',
        'branch_ids' => [$branch->id],
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseMissing('staff_members', ['cafe_id' => $cafe->id]);
}

/** @test */
public function it_rejects_branch_from_another_cafe()
{
    $owner = $this->owner();
    $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
    $otherCafe = Cafe::factory()->create();
    $foreignBranch = Branch::factory()->create(['cafe_id' => $otherCafe->id]);
    Sanctum::actingAs($owner);

    $response = $this->postJson('/api/v1/cafe-admin/staff', [
        'name' => 'Sara',
        'email' => 'sara2@example.com',
        'password' => 'secret123',
        'role' => 'staff',
        'branch_ids' => [$foreignBranch->id],
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseMissing('branch_staff', ['branch_id' => $foreignBranch->id]);
}
```

- [ ] **Step 8: Run the full test class to verify all pass**

Run: `php artisan test --filter=StaffManagementTest`
Expected: PASS (3 tests). The duplicate-email case is caught by the `unique:users,email` rule (422); the foreign-branch case by the ownership guard (422).

- [ ] **Step 9: Commit**

```bash
git add app/Models/User.php app/Services/StaffService.php app/Http/Controllers/StaffController.php tests/Feature/CafeAdmin/StaffManagementTest.php
git commit -m "feat(staff): add staff with branches + owner-set credentials"
```

---

## Task 2: Return assigned branches in staff responses

**Files:**
- Modify: `app/Services/StaffService.php:138-147` (`getStaffDetail`)
- Modify: `app/Http/Resources/StaffResource.php`
- Test: `tests/Feature/CafeAdmin/StaffManagementTest.php` (append)

**Interfaces:**
- Consumes: `User::branchAssignments()` (from Task 1), `Cafe::branches()`.
- Produces: `getStaffDetail()` returns array with an extra `branches` key: `array<int, array{id:int, name:string}>`. `StaffResource` output gains a `branches` array.

- [ ] **Step 1: Write the failing test**

Append to `StaffManagementTest`:

```php
/** @test */
public function staff_detail_includes_assigned_branches()
{
    Notification::fake();
    $owner = $this->owner();
    $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
    $b1 = Branch::factory()->create(['cafe_id' => $cafe->id, 'name' => 'Downtown']);
    $b2 = Branch::factory()->create(['cafe_id' => $cafe->id, 'name' => 'Mall']);
    Sanctum::actingAs($owner);

    $created = $this->postJson('/api/v1/cafe-admin/staff', [
        'name' => 'Sara',
        'email' => 'sara3@example.com',
        'password' => 'secret123',
        'role' => 'staff',
        'branch_ids' => [$b1->id, $b2->id],
    ])->assertStatus(201);

    $staffId = $created->json('data.id');

    $response = $this->getJson("/api/v1/cafe-admin/staff/{$staffId}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['branches' => [['id', 'name']]]]);

    $names = collect($response->json('data.branches'))->pluck('name')->all();
    $this->assertContains('Downtown', $names);
    $this->assertContains('Mall', $names);
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=staff_detail_includes_assigned_branches`
Expected: FAIL — response has no `data.branches` key.

- [ ] **Step 3: Extend `getStaffDetail` to load branches**

Replace `getStaffDetail()` (`app/Services/StaffService.php:138-147`) with:

```php
public function getStaffDetail(StaffMember $staffMember): array
{
    $staffMember->load(['user', 'invitedBy', 'cafe']);
    $permissions = $staffMember->user->getAllPermissions()->pluck('name')->toArray();

    $cafeBranchIds = $staffMember->cafe->branches()->pluck('id')->all();
    $branches = $staffMember->user->branchAssignments()
        ->whereIn('branches.id', $cafeBranchIds)
        ->get(['branches.id', 'branches.name'])
        ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name])
        ->all();

    return [
        'staff_member' => $staffMember,
        'permissions' => $permissions,
        'branches' => $branches,
    ];
}
```

- [ ] **Step 4: Output `branches` from `StaffResource`**

In `app/Http/Resources/StaffResource.php`, after the `$permissions` assignment in `toArray()`, add:

```php
$branches = is_array($this->resource) && isset($this->resource['branches'])
    ? $this->resource['branches']
    : [];
```

Then in the returned array, add a `branches` key next to `permissions`:

```php
            'permissions' => $permissions,
            'branches' => $branches,
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=staff_detail_includes_assigned_branches`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/StaffService.php app/Http/Resources/StaffResource.php tests/Feature/CafeAdmin/StaffManagementTest.php
git commit -m "feat(staff): include assigned branches in staff responses"
```

---

## Task 3: Update staff — sync branches + optional password

**Files:**
- Modify: `app/Services/StaffService.php:152-171` (`updateStaff`)
- Modify: `app/Http/Controllers/StaffController.php:182-245` (`update`)
- Test: `tests/Feature/CafeAdmin/StaffManagementTest.php` (append)

**Interfaces:**
- Consumes: `User::branchAssignments()`.
- Produces: `updateStaff(StaffMember $staffMember, array $data): StaffMember` now honours `data` keys `branch_ids?` (array) and `password?` (string) in addition to `role?` and `permissions?`.

- [ ] **Step 1: Write the failing test**

Append to `StaffManagementTest`:

```php
/** @test */
public function owner_updates_staff_branches_and_password()
{
    Notification::fake();
    $owner = $this->owner();
    $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
    $b1 = Branch::factory()->create(['cafe_id' => $cafe->id]);
    $b2 = Branch::factory()->create(['cafe_id' => $cafe->id]);
    Sanctum::actingAs($owner);

    $staffId = $this->postJson('/api/v1/cafe-admin/staff', [
        'name' => 'Sara',
        'email' => 'sara4@example.com',
        'password' => 'secret123',
        'role' => 'staff',
        'branch_ids' => [$b1->id],
    ])->json('data.id');

    $response = $this->putJson("/api/v1/cafe-admin/staff/{$staffId}", [
        'branch_ids' => [$b2->id],
        'password' => 'newsecret123',
    ]);

    $response->assertStatus(200);

    $user = User::where('email', 'sara4@example.com')->first();
    // branches synced: b1 removed, b2 added
    $this->assertDatabaseMissing('branch_staff', ['branch_id' => $b1->id, 'user_id' => $user->id]);
    $this->assertDatabaseHas('branch_staff', ['branch_id' => $b2->id, 'user_id' => $user->id]);
    // password reset
    $this->assertTrue(Hash::check('newsecret123', $user->fresh()->password));
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=owner_updates_staff_branches_and_password`
Expected: FAIL — `update()` ignores `branch_ids`/`password`; `branch_staff` still has b1, password unchanged.

- [ ] **Step 3: Extend `updateStaff`**

Replace `updateStaff()` (`app/Services/StaffService.php:152-171`) with:

```php
public function updateStaff(StaffMember $staffMember, array $data): StaffMember
{
    return DB::transaction(function () use ($staffMember, $data) {
        if (isset($data['role'])) {
            $staffMember->update(['role' => $data['role']]);
        }

        if (isset($data['permissions'])) {
            $this->syncPermissions($staffMember->user, $data['permissions']);
        } elseif (isset($data['role'])) {
            $this->syncPermissions($staffMember->user, $this->getDefaultPermissionsByRole($data['role']));
        }

        if (isset($data['password'])) {
            $staffMember->user->update(['password' => Hash::make($data['password'])]);
        }

        if (isset($data['branch_ids'])) {
            $role = $data['role'] ?? $staffMember->role;
            $syncData = [];
            foreach ($data['branch_ids'] as $branchId) {
                $syncData[$branchId] = ['role' => $role];
            }
            $staffMember->user->branchAssignments()->sync($syncData);
        }

        return $staffMember->fresh(['user', 'invitedBy']);
    });
}
```

- [ ] **Step 4: Extend `StaffController::update` validation + ownership guard + pass-through**

In `update()` (`app/Http/Controllers/StaffController.php`), replace the validator array (lines 192-196) with:

```php
$validator = Validator::make($request->all(), [
    'role' => 'sometimes|in:admin,manager,staff',
    'permissions' => 'nullable|array',
    'permissions.*' => 'string|in:manage-bookings,view-bookings,manage-matches,view-analytics,manage-offers,manage-menu,manage-branches,manage-seating,scan-qr,check-in-customers,view-occupancy,manage-staff',
    'branch_ids' => 'sometimes|array|min:1',
    'branch_ids.*' => 'integer|exists:branches,id',
    'password' => 'nullable|string|min:8',
]);
```

After the `$staffMember` existence check (after line 224, before the `try`), add the ownership guard for the optional branch list:

```php
if ($request->has('branch_ids')) {
    $ownedBranchIds = $cafe->branches()->pluck('id')->all();
    $invalidBranches = array_diff($request->input('branch_ids', []), $ownedBranchIds);
    if (!empty($invalidBranches)) {
        return response()->json([
            'success' => false,
            'message' => 'One or more branches do not belong to your cafe.',
        ], 422);
    }
}
```

Change the `updateStaff` call (lines 227-230) to pass the new fields:

```php
$updatedStaff = $this->staffService->updateStaff(
    $staffMember,
    $request->only(['role', 'permissions', 'branch_ids', 'password'])
);
```

- [ ] **Step 5: Run the test to verify it passes**

Run: `php artisan test --filter=owner_updates_staff_branches_and_password`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/StaffService.php app/Http/Controllers/StaffController.php tests/Feature/CafeAdmin/StaffManagementTest.php
git commit -m "feat(staff): update staff branches and reset password"
```

---

## Task 4: Removing staff detaches branch assignments

**Files:**
- Modify: `app/Services/StaffService.php:176-206` (`removeStaff`)
- Test: `tests/Feature/CafeAdmin/StaffManagementTest.php` (append)

**Interfaces:**
- Consumes: `User::branchAssignments()`, `Cafe::branches()`.
- Produces: `removeStaff()` additionally detaches the user's `branch_staff` rows for the cafe's branches.

- [ ] **Step 1: Write the failing test**

Append to `StaffManagementTest`:

```php
/** @test */
public function removing_staff_detaches_branch_assignments()
{
    Notification::fake();
    $owner = $this->owner();
    $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
    $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
    Sanctum::actingAs($owner);

    $staffId = $this->postJson('/api/v1/cafe-admin/staff', [
        'name' => 'Sara',
        'email' => 'sara5@example.com',
        'password' => 'secret123',
        'role' => 'staff',
        'branch_ids' => [$branch->id],
    ])->json('data.id');

    $user = User::where('email', 'sara5@example.com')->first();
    $this->assertDatabaseHas('branch_staff', ['branch_id' => $branch->id, 'user_id' => $user->id]);

    $this->deleteJson("/api/v1/cafe-admin/staff/{$staffId}")->assertStatus(200);

    $this->assertDatabaseMissing('branch_staff', ['branch_id' => $branch->id, 'user_id' => $user->id]);
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=removing_staff_detaches_branch_assignments`
Expected: FAIL — the `branch_staff` row still exists after delete.

- [ ] **Step 3: Extend `removeStaff` to detach branches**

In `removeStaff()` (`app/Services/StaffService.php:176-206`), inside the transaction, add the detach immediately before `return $staffMember->delete();`:

```php
            // Detach branch assignments for this cafe's branches.
            $cafeBranchIds = $staffMember->cafe->branches()->pluck('id')->all();
            $user->branchAssignments()->detach($cafeBranchIds);

            // Soft delete the staff member record
            return $staffMember->delete();
```

(`$user` is already defined at the top of the transaction as `$staffMember->user`.)

- [ ] **Step 4: Run the test to verify it passes**

Run: `php artisan test --filter=removing_staff_detaches_branch_assignments`
Expected: PASS.

- [ ] **Step 5: Run the whole feature test class**

Run: `php artisan test --filter=StaffManagementTest`
Expected: PASS (6 tests).

- [ ] **Step 6: Commit**

```bash
git add app/Services/StaffService.php tests/Feature/CafeAdmin/StaffManagementTest.php
git commit -m "feat(staff): detach branch assignments when removing staff"
```

---

## Final verification

- [ ] Run the existing staff suite to confirm no regressions: `php artisan test --filter=StaffTest`
  Expected: PASS (the older invitation/branch-invite tests are untouched).
- [ ] Run the new suite: `php artisan test --filter=StaffManagementTest`
  Expected: PASS (6 tests).
- [ ] Push to `main` so Railway deploys (AWS untouched):
  ```bash
  git push origin main
  ```

## Spec coverage check

- §4 `POST /staff` (branch_ids, password, email unique) → Task 1.
- §4 `PUT /staff/{id}` (branch_ids sync, optional password) → Task 3.
- §4 `GET /staff` & `/{id}` return branches → Task 2 (getStaffDetail feeds StaffResource, used by index/show/store/update).
- §5 data flow (create user w/ password, status accepted, branch_staff, no email) → Task 1.
- §5 remove detaches branch_staff → Task 4.
- §6 response `branches` → Task 2.
- §7 security (manage-staff gate, subscription limit, branch ownership, unique email, hashed password) → Tasks 1 & 3 (gate + limit already present; guards added).
- §8 tests → one test per Task, 6 total.
- §9 non-goals honoured (no UI, `branch_staff_permissions` untouched, branch-level invite endpoints untouched).
