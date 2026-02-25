<?php

namespace Tests\Feature\Cafes;

use App\Models\Cafe;
use App\Models\Branch;
use App\Models\BranchHour;
use App\Models\Amenity;
use App\Models\GameMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_branch_detail_with_hours()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        BranchHour::factory()->create([
            'branch_id' => $branch->id,
            'day_of_week' => 'Monday',
            'open_time' => '09:00',
            'close_time' => '23:00',
        ]);

        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'address',
                    'hours' => [
                        '*' => ['day_of_week', 'open_time', 'close_time'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_branch_detail_with_amenities()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        $amenity1 = Amenity::factory()->create(['name' => 'WiFi']);
        $amenity2 = Amenity::factory()->create(['name' => 'Parking']);
        
        $branch->amenities()->attach([$amenity1->id, $amenity2->id]);

        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'amenities' => [
                        '*' => ['id', 'name', 'icon'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data.amenities'));
    }

    /** @test */
    public function it_returns_branch_matches()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        GameMatch::factory()->count(3)->create([
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/v1/branches/{$branch->id}/matches");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'home_team', 'away_team', 'match_date'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_returns_only_published_matches_for_branch()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        GameMatch::factory()->count(2)->create([
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);
        
        GameMatch::factory()->count(3)->create([
            'branch_id' => $branch->id,
            'is_published' => false,
        ]);

        $response = $this->getJson("/api/v1/branches/{$branch->id}/matches");

        $response->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_returns_404_for_nonexistent_branch()
    {
        $response = $this->getJson('/api/v1/branches/99999');

        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ])
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_branch_with_full_details()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create([
            'cafe_id' => $cafe->id,
            'name' => 'Main Branch',
            'address' => '123 Test Street',
            'phone' => '+966512345678',
        ]);

        $response = $this->getJson("/api/v1/branches/{$branch->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Main Branch',
                    'address' => '123 Test Street',
                    'phone' => '+966512345678',
                ],
            ]);
    }

    /** @test */
    public function it_lists_all_branches_for_cafe()
    {
        $cafe = Cafe::factory()->create();
        Branch::factory()->count(3)->create(['cafe_id' => $cafe->id]);

        $response = $this->getJson("/api/v1/cafes/{$cafe->id}/branches");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'address'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }
}

