<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Branch;
use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\SubscriptionPlan;
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

    /**
     * Seed permissions, create a cafe owner + their cafe with an ACTIVE
     * subscription that allows staff (store() enforces canAddStaff).
     *
     * @return array{0: User, 1: Cafe}
     */
    private function activeCafeOwner(): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create([
            'max_staff_members' => 10,
            'is_active' => true,
        ]);
        CafeSubscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->addMonth(),
        ]);

        return [$owner, $cafe];
    }

    /** @test */
    public function owner_adds_staff_with_branches_and_credentials()
    {
        Notification::fake();
        [$owner, $cafe] = $this->activeCafeOwner();
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

    /** @test */
    public function it_rejects_duplicate_email()
    {
        [$owner, $cafe] = $this->activeCafeOwner();
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
        [$owner, $cafe] = $this->activeCafeOwner();
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

    /** @test */
    public function staff_detail_includes_assigned_branches()
    {
        Notification::fake();
        [$owner, $cafe] = $this->activeCafeOwner();
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

    /** @test */
    public function owner_updates_staff_branches_and_password()
    {
        Notification::fake();
        [$owner, $cafe] = $this->activeCafeOwner();
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

    /** @test */
    public function removing_staff_detaches_branch_assignments()
    {
        Notification::fake();
        [$owner, $cafe] = $this->activeCafeOwner();
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
}
