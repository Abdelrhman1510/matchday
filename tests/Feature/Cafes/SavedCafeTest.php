<?php

namespace Tests\Feature\Cafes;

use App\Models\User;
use App\Models\Cafe;
use App\Models\SavedCafe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavedCafeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_saves_cafe_successfully()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/cafes/{$cafe->id}/save");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data',
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('saved_cafes', [
            'user_id' => $user->id,
            'cafe_id' => $cafe->id,
        ]);
    }

    /** @test */
    public function it_lists_saved_cafes()
    {
        $user = User::factory()->create();
        $cafes = Cafe::factory()->count(3)->create();
        Sanctum::actingAs($user);

        foreach ($cafes as $cafe) {
            SavedCafe::factory()->create([
                'user_id' => $user->id,
                'cafe_id' => $cafe->id,
            ]);
        }

        $response = $this->getJson('/api/v1/profile/saved-cafes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'logo'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_unsaves_cafe_successfully()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        Sanctum::actingAs($user);

        SavedCafe::factory()->create([
            'user_id' => $user->id,
            'cafe_id' => $cafe->id,
        ]);

        $response = $this->deleteJson("/api/v1/cafes/{$cafe->id}/unsave");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('saved_cafes', [
            'user_id' => $user->id,
            'cafe_id' => $cafe->id,
        ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_save()
    {
        $cafe = Cafe::factory()->create();

        $response = $this->postJson("/api/v1/cafes/{$cafe->id}/save");

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_cafe()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/cafes/99999/save');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_prevents_duplicate_saves()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        Sanctum::actingAs($user);

        SavedCafe::factory()->create([
            'user_id' => $user->id,
            'cafe_id' => $cafe->id,
        ]);

        $response = $this->postJson("/api/v1/cafes/{$cafe->id}/save");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_empty_array_when_no_saved_cafes()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/profile/saved-cafes');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }
}
