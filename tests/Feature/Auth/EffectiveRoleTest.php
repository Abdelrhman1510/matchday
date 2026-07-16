<?php

namespace Tests\Feature\Auth;

use App\Models\Branch;
use App\Models\Cafe;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EffectiveRoleTest extends TestCase
{
    use RefreshDatabase;

    /** Owner + cafe + a branch, staff created as a manager on that branch. */
    protected function cafeWithManager(array $permissions = ['view-bookings', 'manage-bookings']): array
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create(['password' => bcrypt('secret123')]);
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);

        // Staff row: account role hardcoded to 'staff', sub-role is 'manager'.
        $manager = User::factory()->staff()->create(['password' => bcrypt('secret123')]);
        $manager->staffMemberships()->create([
            'cafe_id' => $cafe->id, 'role' => 'manager', 'invitation_status' => 'accepted',
        ]);
        $manager->branchAssignments()->attach($branch->id, ['role' => 'manager']);
        foreach ($permissions as $p) {
            $manager->givePermissionTo($p);
        }

        return [$owner, $manager, $cafe, $branch];
    }

    /** @test */
    public function login_returns_effective_role_for_manager()
    {
        [$owner, $manager] = $this->cafeWithManager();

        $res = $this->postJson('/api/v1/auth/login', [
            'email_or_phone' => $manager->email,
            'password' => 'secret123',
        ])->assertStatus(200);

        // Account column is 'staff', but the effective cafe role is 'manager'.
        $this->assertEquals('manager', $res->json('data.user.role'));
        $perms = $res->json('data.user.permissions');
        $this->assertContains('view-bookings', $perms);
        $this->assertContains('manage-bookings', $perms);
        $this->assertNotContains('manage-staff', $perms);
    }

    /** @test */
    public function login_returns_owner_role_and_full_catalog()
    {
        [$owner] = $this->cafeWithManager();

        $res = $this->postJson('/api/v1/auth/login', [
            'email_or_phone' => $owner->email,
            'password' => 'secret123',
        ])->assertStatus(200);

        $this->assertEquals('cafe_owner', $res->json('data.user.role'));
        $perms = $res->json('data.user.permissions');
        $this->assertContains('manage-staff', $perms);
        $this->assertContains('manage-subscription', $perms);
        $this->assertContains('manage-bookings', $perms);
    }

    /** @test */
    public function me_endpoint_returns_effective_role_and_permissions()
    {
        [$owner, $manager] = $this->cafeWithManager();
        Sanctum::actingAs($manager);

        $res = $this->getJson('/api/v1/auth/me')->assertStatus(200);

        $this->assertEquals('manager', $res->json('data.user.role'));
        $this->assertContains('view-bookings', $res->json('data.user.permissions'));
    }

    /** @test */
    public function profile_endpoint_returns_effective_role_and_permissions()
    {
        [$owner, $manager] = $this->cafeWithManager();
        Sanctum::actingAs($manager);

        $res = $this->getJson('/api/v1/profile')->assertStatus(200);

        $this->assertEquals('manager', $res->json('data.role'));
        $this->assertContains('view-bookings', $res->json('data.permissions'));
    }
}
