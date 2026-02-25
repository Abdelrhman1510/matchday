<?php

namespace Tests\Feature\Teams;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_all_teams()
    {
        Team::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/teams');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'logo', 'country'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_filters_teams_by_league()
    {
        Team::factory()->create(['league' => 'Premier League']);
        Team::factory()->create(['league' => 'La Liga']);
        Team::factory()->count(2)->create(['league' => 'Premier League']);

        $response = $this->getJson('/api/v1/teams?league=Premier League');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_searches_teams_by_name()
    {
        Team::factory()->create(['name' => 'Manchester United']);
        Team::factory()->create(['name' => 'Manchester City']);
        Team::factory()->create(['name' => 'Liverpool']);

        $response = $this->getJson('/api/v1/teams?search=Manchester');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_returns_popular_teams()
    {
        Team::factory()->count(3)->create(['is_popular' => true]);
        Team::factory()->count(2)->create(['is_popular' => false]);

        $response = $this->getJson('/api/v1/teams/popular');

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
    public function it_returns_single_team()
    {
        $team = Team::factory()->create([
            'name' => 'Barcelona',
            'country' => 'Spain',
        ]);

        $response = $this->getJson("/api/v1/teams/{$team->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'logo', 'country', 'league'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Barcelona',
                    'country' => 'Spain',
                ],
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_team()
    {
        $response = $this->getJson('/api/v1/teams/99999');

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
    public function it_filters_teams_by_country()
    {
        Team::factory()->count(2)->create(['country' => 'England']);
        Team::factory()->count(3)->create(['country' => 'Spain']);

        $response = $this->getJson('/api/v1/teams?country=England');

        $response->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_returns_teams_without_authentication()
    {
        Team::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/teams');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_teams_with_authentication()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        Team::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/teams');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_empty_array_when_no_teams_exist()
    {
        $response = $this->getJson('/api/v1/teams');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [],
            ]);
    }
}
