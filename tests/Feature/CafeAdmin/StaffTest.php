<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\User;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\StaffInvitation;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StaffTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_invites_staff_member()
    {
        Mail::fake();
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/staff/invite", [
            'email' => 'staff@example.com',
            'role' => 'manager',
            'permissions' => ['view-bookings', 'manage-matches'],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['invitation_id', 'email', 'status'],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['status' => 'pending'],
            ]);

        $this->assertDatabaseHas('staff_invitations', [
            'branch_id' => $branch->id,
            'email' => 'staff@example.com',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function it_accepts_staff_invitation()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        $staffUser = User::factory()->create(['email' => 'staff@example.com']);
        
        $invitation = StaffInvitation::factory()->create([
            'branch_id' => $branch->id,
            'email' => 'staff@example.com',
            'status' => 'pending',
            'token' => 'invitation-token-123',
        ]);
        
        Sanctum::actingAs($staffUser);

        $response = $this->postJson('/api/v1/staff/invitations/accept', [
            'token' => 'invitation-token-123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('staff_invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);

        $this->assertDatabaseHas('branch_staff', [
            'branch_id' => $branch->id,
            'user_id' => $staffUser->id,
        ]);
    }

    /** @test */
    public function it_lists_branch_staff()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        // Add staff members
        $staff1 = User::factory()->create();
        $staff2 = User::factory()->create();
        
        $branch->staff()->attach($staff1->id, ['role' => 'manager']);
        $branch->staff()->attach($staff2->id, ['role' => 'staff']);
        
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/staff");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'email', 'role', 'permissions'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_updates_staff_permissions()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $staffUser = User::factory()->create();
        
        $branch->staff()->attach($staffUser->id, ['role' => 'staff']);
        
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/branches/{$branch->id}/staff/{$staffUser->id}/permissions", [
            'permissions' => ['view-bookings', 'manage-matches', 'view-analytics'],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('branch_staff_permissions', [
            'user_id' => $staffUser->id,
            'branch_id' => $branch->id,
            'permission' => 'view-bookings',
        ]);
    }

    /** @test */
    public function it_enforces_staff_permissions()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $staffUser = User::factory()->create();
        
        // Assign staff WITHOUT manage-matches permission
        $branch->staff()->attach($staffUser->id, ['role' => 'staff']);
        
        Sanctum::actingAs($staffUser);

        // Try to create match (requires manage-matches permission)
        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/matches", [
            'home_team_id' => 1,
            'away_team_id' => 2,
            'match_date' => now()->addDays(5),
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_allows_staff_with_permission()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $staffUser = User::factory()->create();
        
        // Assign staff WITH view-bookings permission
        $branch->staff()->attach($staffUser->id, ['role' => 'staff']);
        Permission::create([
            'user_id' => $staffUser->id,
            'branch_id' => $branch->id,
            'permission' => 'view-bookings',
        ]);
        
        Sanctum::actingAs($staffUser);

        // View bookings should be allowed
        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/bookings");

        $response->assertStatus(200);
    }

    /** @test */
    public function it_removes_staff_member()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $staffUser = User::factory()->create();
        
        $branch->staff()->attach($staffUser->id, ['role' => 'staff']);
        
        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/admin/branches/{$branch->id}/staff/{$staffUser->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('branch_staff', [
            'branch_id' => $branch->id,
            'user_id' => $staffUser->id,
        ]);
    }

    /** @test */
    public function it_revokes_permissions_when_removing_staff()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $staffUser = User::factory()->create();
        
        $branch->staff()->attach($staffUser->id, ['role' => 'staff']);
        Permission::create([
            'user_id' => $staffUser->id,
            'branch_id' => $branch->id,
            'permission' => 'view-bookings',
        ]);
        
        Sanctum::actingAs($owner);

        $response = $this->deleteJson("/api/v1/admin/branches/{$branch->id}/staff/{$staffUser->id}");

        $response->assertStatus(200);

        // Verify permissions revoked
        $this->assertDatabaseMissing('branch_staff_permissions', [
            'user_id' => $staffUser->id,
            'branch_id' => $branch->id,
        ]);
    }

    /** @test */
    public function it_returns_403_for_non_owner_inviting_staff()
    {
        $fan = User::factory()->fan()->create();
        $branch = Branch::factory()->create();
        Sanctum::actingAs($fan);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/staff/invite", [
            'email' => 'staff@example.com',
            'role' => 'manager',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_invitation_token()
    {
        $staffUser = User::factory()->create();
        Sanctum::actingAs($staffUser);

        $response = $this->postJson('/api/v1/staff/invitations/accept', [
            'token' => 'invalid-token',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }
}
