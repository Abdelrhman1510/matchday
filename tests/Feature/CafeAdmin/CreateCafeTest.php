<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Cafe;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateCafeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_new_owner_with_no_cafe_can_create_their_first_cafe()
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        // A cafe owner who has NOT created a cafe yet (the onboarding case).
        $owner = User::factory()->cafeOwner()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/cafe-admin/cafe', [
            'name' => 'My New Cafe',
            'description' => 'Best matches in town',
            'phone' => '+966512345678',
            'city' => 'Riyadh',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'My New Cafe');

        $this->assertDatabaseHas('cafes', [
            'owner_id' => $owner->id,
            'name' => 'My New Cafe',
        ]);
    }

    /** @test */
    public function creating_a_second_cafe_is_rejected()
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $owner = User::factory()->cafeOwner()->create();
        Cafe::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/v1/cafe-admin/cafe', ['name' => 'Second Cafe'])
            ->assertStatus(400);
    }
}
