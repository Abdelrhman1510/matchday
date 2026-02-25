<?php

namespace Tests\Feature\Home;

use App\Models\User;
use App\Models\GameMatch;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\Offer;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HomeTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_authenticated_home_feed()
    {
        $user = User::factory()->create();
        
        GameMatch::factory()->count(3)->create([
            'is_published' => true,
            'match_date' => now()->addDays(2),
        ]);
        Cafe::factory()->count(2)->create(['is_featured' => true]);
        Offer::factory()->count(2)->create(['status' => 'active']);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/home');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'upcoming_matches' => [
                        '*' => ['id', 'home_team', 'away_team', 'match_date'],
                    ],
                    'featured_cafes' => [
                        '*' => ['id', 'name', 'logo'],
                    ],
                    'active_offers' => [
                        '*' => ['id', 'title', 'discount'],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_explore_aggregated_data()
    {
        GameMatch::factory()->count(5)->create(['status' => 'published']);
        Cafe::factory()->count(3)->create();
        Team::factory()->count(4)->create(['is_popular' => true]);
        
        $response = $this->getJson('/api/v1/explore');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'matches',
                    'cafes',
                    'popular_teams',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_searches_across_all_resources()
    {
        $cafe = Cafe::factory()->create(['name' => 'Sports Arena Cafe']);
        Team::factory()->create(['name' => 'Sports FC']);
        $match = GameMatch::factory()->create([
            'is_published' => true,
        ]);
        
        $response = $this->getJson('/api/v1/search?query=Sports');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'cafes',
                    'teams',
                    'matches',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_personalized_feed_for_authenticated_user()
    {
        $user = User::factory()->create();
        $favoriteTeam = Team::factory()->create();
        $user->fanProfile->update(['favorite_team_id' => $favoriteTeam->id]);
        
        // Matches with favorite team
        GameMatch::factory()->count(2)->create([
            'home_team_id' => $favoriteTeam->id,
            'is_published' => true,
            'match_date' => now()->addDays(3),
        ]);
        
        GameMatch::factory()->count(3)->create([
            'is_published' => true,
            'match_date' => now()->addDays(5),
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/home/feed');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'recommended_matches',
                    'nearby_cafes',
                    'personalized_offers',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_empty_search_for_no_results()
    {
        $response = $this->getJson('/api/v1/search?query=NonexistentQuery');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'cafes' => [],
                    'teams' => [],
                    'matches' => [],
                ],
            ]);
    }

    /** @test */
    public function it_returns_422_for_short_search_query()
    {
        $response = $this->getJson('/api/v1/search?query=ab');

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['query'],
            ]);
    }

    /** @test */
    public function it_returns_home_feed_without_authentication()
    {
        GameMatch::factory()->count(3)->create(['status' => 'published']);
        Cafe::factory()->count(2)->create();
        
        $response = $this->getJson('/api/v1/home');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_filters_search_by_type()
    {
        Cafe::factory()->create(['name' => 'Test Cafe']);
        Team::factory()->create(['name' => 'Test Team']);
        
        $response = $this->getJson('/api/v1/search?query=Test&type=cafes');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertArrayHasKey('cafes', $response->json('data'));
        $this->assertCount(1, $response->json('data.cafes'));
    }

    /** @test */
    public function it_returns_trending_content()
    {
        GameMatch::factory()->count(3)->create([
            'is_published' => true,
            'is_trending' => true,
        ]);
        
        $response = $this->getJson('/api/v1/home/trending');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'trending_matches',
                    'trending_cafes',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }
}
