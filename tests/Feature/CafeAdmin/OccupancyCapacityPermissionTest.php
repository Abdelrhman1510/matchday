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

class OccupancyCapacityPermissionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Cafe, 2: Branch} */
    protected function cafeWithOccupancy(): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $plan = SubscriptionPlan::factory()->create([
            'is_active' => true, 'has_occupancy_tracking' => true, 'max_staff_members' => 10,
        ]);
        CafeSubscription::factory()->create([
            'cafe_id' => $cafe->id, 'plan_id' => $plan->id, 'status' => 'active',
            'starts_at' => now()->subMonth(), 'expires_at' => now()->addMonth(),
        ]);
        return [$owner, $cafe, $branch];
    }

    protected function makeStaff(Cafe $cafe, array $branchIds, array $permissions): User
    {
        $staff = User::factory()->staff()->create();
        $staff->staffMemberships()->create([
            'cafe_id' => $cafe->id, 'role' => 'manager', 'invitation_status' => 'accepted',
        ]);
        foreach ($branchIds as $bid) {
            $staff->branchAssignments()->attach($bid, ['role' => 'manager']);
        }
        foreach ($permissions as $p) {
            $staff->givePermissionTo($p);
        }
        return $staff;
    }

    /** @test */
    public function staff_with_manage_seating_can_update_capacity()
    {
        [$owner, $cafe, $branch] = $this->cafeWithOccupancy();
        $staff = $this->makeStaff($cafe, [$branch->id], ['manage-seating']);
        Sanctum::actingAs($staff);

        $this->putJson('/api/v1/cafe-admin/occupancy/capacity', ['total_capacity' => 120])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function owner_can_still_update_capacity()
    {
        [$owner, $cafe, $branch] = $this->cafeWithOccupancy();
        Sanctum::actingAs($owner);

        $this->putJson('/api/v1/cafe-admin/occupancy/capacity', ['total_capacity' => 120])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function staff_without_manage_seating_is_forbidden()
    {
        [$owner, $cafe, $branch] = $this->cafeWithOccupancy();
        $staff = $this->makeStaff($cafe, [$branch->id], ['view-occupancy']); // no manage-seating
        Sanctum::actingAs($staff);

        $this->putJson('/api/v1/cafe-admin/occupancy/capacity', ['total_capacity' => 120])
            ->assertStatus(403);
    }
}
