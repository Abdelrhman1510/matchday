<?php

namespace Tests\Feature\Cafes;

use App\Models\Cafe;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CafeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_cafes_paginated()
    {
        Cafe::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/cafes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'logo', 'description'],
                    ],
                    'current_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_filters_featured_cafes()
    {
        Cafe::factory()->count(3)->create(['is_featured' => true]);
        Cafe::factory()->count(2)->create(['is_featured' => false]);

        $response = $this->getJson('/api/v1/cafes?featured=true');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data.data'));
    }

    /** @test */
    public function it_searches_cafes_by_name()
    {
        Cafe::factory()->create(['name' => 'Sports Cafe']);
        Cafe::factory()->create(['name' => 'Game Zone Cafe']);
        Cafe::factory()->create(['name' => 'Coffee House']);

        $response = $this->getJson('/api/v1/cafes?search=Cafe');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    /** @test */
    public function it_finds_nearby_cafes_with_coordinates()
    {
        // Create cafes with different locations
        $cafe1 = Cafe::factory()->create();
        Branch::factory()->create([
            'cafe_id' => $cafe1->id,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $cafe2 = Cafe::factory()->create();
        Branch::factory()->create([
            'cafe_id' => $cafe2->id,
            'latitude' => 24.7200,
            'longitude' => 46.6800,
        ]);

        $response = $this->getJson('/api/v1/cafes/nearby?latitude=24.7136&longitude=46.6753&radius=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'distance'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_422_for_invalid_coordinates()
    {
        $response = $this->getJson('/api/v1/cafes/nearby?latitude=invalid&longitude=46.6753');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    /** @test */
    public function it_returns_single_cafe_details()
    {
        $cafe = Cafe::factory()->create([
            'name' => 'Test Cafe',
            'description' => 'A great place to watch matches',
        ]);

        $response = $this->getJson("/api/v1/cafes/{$cafe->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'logo', 'description'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Test Cafe',
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_cafe()
    {
        $response = $this->getJson('/api/v1/cafes/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_lists_cafes_without_authentication()
    {
        Cafe::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/cafes');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_lists_cafes_with_authentication()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        Cafe::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/cafes');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_paginates_cafes_correctly()
    {
        Cafe::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/cafes?per_page=10');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'per_page' => 10,
                    'total' => 25,
                ],
            ]);

        $this->assertCount(10, $response->json('data.data'));
    }
}
