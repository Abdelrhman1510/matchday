<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\User;
use App\Models\Cafe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CafeAdminTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_cafe_successfully()
    {
        $owner = User::factory()->cafeOwner()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/v1/admin/cafes', [
            'name' => 'Sports Arena Cafe',
            'description' => 'Best place to watch matches',
            'phone' => '+966512345678',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'description', 'owner_id'],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('cafes', [
            'name' => 'Sports Arena Cafe',
            'owner_id' => $owner->id,
        ]);
    }

    /** @test */
    public function it_updates_cafe()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/cafes/{$cafe->id}", [
            'name' => 'Updated Cafe Name',
            'description' => 'Updated description',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Updated Cafe Name',
                ],
            ]);

        $this->assertDatabaseHas('cafes', [
            'id' => $cafe->id,
            'name' => 'Updated Cafe Name',
        ]);
    }

    /** @test */
    public function it_uploads_cafe_logo()
    {
        Storage::fake('public');
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $file = UploadedFile::fake()->image('logo.png');

        $response = $this->postJson("/api/v1/admin/cafes/{$cafe->id}/logo", [
            'logo' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['logo_url'],
            ])
            ->assertJson([
                'success' => true,
            ]);

        Storage::disk('public')->assertExists('logos/' . $file->hashName());
    }

    /** @test */
    public function it_returns_cafe_onboarding_status()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/cafes/{$cafe->id}/onboarding");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'steps' => [
                        'cafe_created',
                        'branch_added',
                        'seating_configured',
                        'match_published',
                    ],
                    'progress_percentage',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_403_for_fan_creating_cafe()
    {
        $fan = User::factory()->fan()->create();
        Sanctum::actingAs($fan);

        $response = $this->postJson('/api/v1/admin/cafes', [
            'name' => 'Test Cafe',
            'description' => 'Description',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_403_for_other_owner_updating_cafe()
    {
        $owner1 = User::factory()->cafeOwner()->create();
        $owner2 = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner1->id]);
        
        Sanctum::actingAs($owner2);

        $response = $this->putJson("/api/v1/admin/cafes/{$cafe->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_lists_owner_cafes()
    {
        $owner = User::factory()->cafeOwner()->create();
        Cafe::factory()->count(3)->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/v1/admin/cafes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'logo', 'branches_count'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_returns_422_for_invalid_logo_file()
    {
        Storage::fake('public');
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        Sanctum::actingAs($owner);

        $file = UploadedFile::fake()->create('document.pdf');

        $response = $this->postJson("/api/v1/admin/cafes/{$cafe->id}/logo", [
            'logo' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['logo'],
            ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_cafe_creation()
    {
        $response = $this->postJson('/api/v1/admin/cafes', [
            'name' => 'Test Cafe',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }
}
