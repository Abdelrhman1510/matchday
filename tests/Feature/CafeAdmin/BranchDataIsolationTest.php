<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\GameMatch;
use App\Models\Offer;
use App\Models\SeatingSection;
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
            'is_active' => true, 'has_analytics' => true, 'has_occupancy_tracking' => true,
            'max_staff_members' => 10,
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

    /** @test */
    public function staff_occupancy_resolves_an_assigned_branch()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        // Checked-in visitors exist ONLY on branch B today.
        $matchB = GameMatch::factory()->create(['branch_id' => $branchB->id]);
        Booking::factory()->create([
            'branch_id' => $branchB->id, 'match_id' => $matchB->id,
            'status' => 'checked_in', 'checked_in_at' => now(), 'guests_count' => 7,
        ]);
        // Staff assigned ONLY to branch B (not the cafe's first branch A).
        $staff = $this->makeStaff($cafe, [$branchB->id], ['view-occupancy']);
        Sanctum::actingAs($staff);

        $res = $this->getJson('/api/v1/cafe-admin/occupancy')->assertStatus(200);
        // If occupancy resolved branch A (old behavior), visitors would be 0.
        $this->assertEquals(7, $res->json('data.today_total_visitors'));
    }

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

        $this->getJson("/api/v1/admin/branches/{$branchB->id}/analytics/dashboard")
            ->assertStatus(403);
    }

    /** @test */
    public function staff_dashboard_recent_bookings_shows_only_assigned_branch()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $matchA = GameMatch::factory()->create(['branch_id' => $branchA->id]);
        $matchB = GameMatch::factory()->create(['branch_id' => $branchB->id]);
        $bookingA = Booking::factory()->create(['branch_id' => $branchA->id, 'match_id' => $matchA->id]);
        $bookingB = Booking::factory()->create(['branch_id' => $branchB->id, 'match_id' => $matchB->id]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['view-analytics']);
        Sanctum::actingAs($staff);

        $res = $this->getJson('/api/v1/cafe-admin/dashboard/recent-bookings')->assertStatus(200);
        $ids = collect($res->json('data'))->pluck('booking_id')->all();
        $this->assertContains($bookingA->id, $ids);
        $this->assertNotContains($bookingB->id, $ids);
    }

    /** @test */
    public function staff_customer_analytics_scoped_to_assigned_branch()
    {
        [$owner, $cafe, $branchA, $branchB] = $this->isolationCafe();
        $matchA = GameMatch::factory()->create(['branch_id' => $branchA->id]);
        $matchB = GameMatch::factory()->create(['branch_id' => $branchB->id]);
        $custA = User::factory()->create();
        $custB = User::factory()->create();
        Booking::factory()->create(['branch_id' => $branchA->id, 'match_id' => $matchA->id, 'user_id' => $custA->id]);
        Booking::factory()->create(['branch_id' => $branchB->id, 'match_id' => $matchB->id, 'user_id' => $custB->id]);
        $staff = $this->makeStaff($cafe, [$branchA->id], ['view-analytics']);
        Sanctum::actingAs($staff);

        $res = $this->getJson('/api/v1/cafe-admin/analytics/customers')->assertStatus(200);
        // Only branch A's single customer is counted, not branch B's.
        $this->assertEquals(1, $res->json('data.new_count'));
    }

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
}
