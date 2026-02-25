<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\User;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\BranchHour;
use App\Models\Amenity;
use App\Models\CafeSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BranchAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Create an active subscription for the given cafe with generous limits.
     */
    private function createActiveSubscription(Cafe $cafe): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'max_branches' => 10,
            'max_matches_per_month' => 100,
            'max_bookings_per_month' => 500,
            'max_staff_members' => 20,
            'max_offers' => 20,
        ]);

        CafeSubscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    /** @test */
    public function it_creates_branch_successfully()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $this->createActiveSubscription($cafe);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/cafes/{$cafe->id}/branches", [
            'name' => 'Main Branch',
            'address' => '123 Test Street',
            'city' => 'Riyadh',
            'phone' => '+966512345678',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'address', 'city'],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('branches', [
            'cafe_id' => $cafe->id,
            'name' => 'Main Branch',
        ]);
    }

    /** @test */
    public function it_updates_branch()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/branches/{$branch->id}", [
            'name' => 'Updated Branch Name',
            'address' => 'New Address',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Branch Name',
                ],
            ]);
    }

    /** @test */
    public function it_deletes_branch()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/admin/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertSoftDeleted('branches', [
            'id' => $branch->id,
        ]);
    }

    /** @test */
    public function it_sets_branch_hours()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/hours", [
            'hours' => [
                ['day_of_week' => 'Monday', 'open_time' => '09:00', 'close_time' => '23:00'],
                ['day_of_week' => 'Tuesday', 'open_time' => '09:00', 'close_time' => '23:00'],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('branch_hours', [
            'branch_id' => $branch->id,
            'day_of_week' => 'Monday',
        ]);
    }

    /** @test */
    public function it_sets_branch_amenities()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $amenity1 = Amenity::factory()->create(['name' => 'WiFi']);
        $amenity2 = Amenity::factory()->create(['name' => 'Parking']);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/amenities", [
            'amenity_ids' => [$amenity1->id, $amenity2->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('branch_amenity', [
            'branch_id' => $branch->id,
            'amenity_id' => $amenity1->id,
        ]);
    }

    /** @test */
    public function it_returns_branch_overview()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/overview");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'branch',
                    'stats' => [
                        'total_matches',
                        'total_bookings',
                        'total_revenue',
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_branch_setup_progress()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/setup-progress");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'hours_configured',
                    'amenities_added',
                    'seating_configured',
                    'progress_percentage',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_403_for_other_owner_branch()
    {
        $owner1 = User::factory()->cafeOwner()->create();
        $owner2 = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner1->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);

        Sanctum::actingAs($owner2);

        $response = $this->putJson("/api/v1/admin/branches/{$branch->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_403_for_fan_accessing_branch_admin()
    {
        $fan = User::factory()->fan()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);

        Sanctum::actingAs($fan);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_coordinates()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $this->createActiveSubscription($cafe);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/cafes/{$cafe->id}/branches", [
            'name' => 'Branch',
            'latitude' => 999,
            'longitude' => 999,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }
}
