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

    /** @test */
    public function staff_with_permission_can_hit_gated_endpoint()
    {
        [$owner, $cafe, $branch] = $this->cafeWithOwner();
        $staff = $this->makeStaff($cafe, [$branch->id], ['view-bookings']);
        Sanctum::actingAs($staff);

        $this->getJson('/api/v1/cafe-admin/bookings')->assertStatus(200);
    }

    /** @test */
    public function staff_without_permission_is_forbidden()
    {
        [$owner, $cafe, $branch] = $this->cafeWithOwner();
        $staff = $this->makeStaff($cafe, [$branch->id], []); // no view-bookings
        Sanctum::actingAs($staff);

        $this->getJson('/api/v1/cafe-admin/bookings')
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

        $this->getJson('/api/v1/cafe-admin/bookings')->assertStatus(200);
        $this->getJson('/api/v1/cafe-admin/subscription')->assertStatus(200);
    }

    /** @test */
    public function staff_can_reach_subscription_gated_endpoint_with_permission()
    {
        // Cafe on a plan that includes analytics; staff granted view-analytics.
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $plan = SubscriptionPlan::factory()->create([
            'is_active' => true, 'has_analytics' => true, 'max_staff_members' => 10,
        ]);
        CafeSubscription::factory()->create([
            'cafe_id' => $cafe->id, 'plan_id' => $plan->id, 'status' => 'active',
            'starts_at' => now()->subMonth(), 'expires_at' => now()->addMonth(),
        ]);
        $staff = $this->makeStaff($cafe, [$branch->id], ['view-analytics']);
        Sanctum::actingAs($staff);

        $this->getJson('/api/v1/cafe-admin/analytics/overview')->assertStatus(200);
    }
}
