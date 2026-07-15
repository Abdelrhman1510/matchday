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
        $this->postJson("/api/v1/cafe-admin/matches/{$matchB->id}/publish")->assertStatus(403);
    }
}
