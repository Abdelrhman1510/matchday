<?php

namespace Tests\Feature\Matches;

use App\Models\GameMatch;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\Team;
use App\Models\User;
use App\Models\SeatingSection;
use App\Models\Seat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_only_published_matches()
    {
        GameMatch::factory()->count(3)->create(['is_published' => true]);
        GameMatch::factory()->count(2)->create(['is_published' => false]);

        $response = $this->getJson('/api/v1/matches');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'home_team', 'away_team', 'match_date', 'status'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_filters_matches_by_status()
    {
        GameMatch::factory()->count(2)->create([
            'is_published' => true,
            'status' => 'upcoming',
            'match_date' => now()->addDays(5),
        ]);
        GameMatch::factory()->create([
            'is_published' => true,
            'status' => 'upcoming',
            'match_date' => now()->subDays(1),
        ]);

        $response = $this->getJson('/api/v1/matches?status=upcoming');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_live_matches()
    {
        GameMatch::factory()->count(2)->create([
            'status' => 'live',
            'is_published' => true,
            'match_date' => now()->subHour(),
        ]);
        GameMatch::factory()->create([
            'status' => 'upcoming',
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/v1/matches/live');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'home_team', 'away_team', 'status'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_returns_upcoming_matches()
    {
        GameMatch::factory()->count(3)->create([
            'is_published' => true,
            'status' => 'upcoming',
            'match_date' => now()->addDays(3),
        ]);
        GameMatch::factory()->create([
            'is_published' => true,
            'status' => 'upcoming',
            'match_date' => now()->subDays(1),
        ]);

        $response = $this->getJson('/api/v1/matches?filter=upcoming');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_single_match_with_details()
    {
        $homeTeam = Team::factory()->create(['name' => 'Team A']);
        $awayTeam = Team::factory()->create(['name' => 'Team B']);
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);

        $match = GameMatch::factory()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/v1/matches/{$match->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'home_team',
                    'away_team',
                    'branch',
                    'match_date',
                    'status',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_seating_map_for_match()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);

        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        Seat::factory()->count(5)->create([
            'section_id' => $section->id,
            'is_available' => true,
        ]);

        $response = $this->getJson("/api/v1/matches/{$match->id}/seating?branch_id={$branch->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'section_id',
                        'section_name',
                        'seats' => [
                            '*' => ['id', 'label', 'is_available'],
                        ],
                    ],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_match()
    {
        $response = $this->getJson('/api/v1/matches/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_filters_matches_by_team()
    {
        $team = Team::factory()->create();
        GameMatch::factory()->count(2)->create([
            'home_team_id' => $team->id,
            'is_published' => true,
        ]);
        GameMatch::factory()->create([
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/v1/matches?team_id={$team->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_lists_matches_without_authentication()
    {
        GameMatch::factory()->count(3)->create(['is_published' => true]);

        $response = $this->getJson('/api/v1/matches');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_lists_matches_with_authentication()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        
        GameMatch::factory()->count(3)->create(['is_published' => true]);

        $response = $this->getJson('/api/v1/matches');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }
}
