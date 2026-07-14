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
