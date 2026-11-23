<?php

namespace Tests\Feature\Cafes;

use App\Models\Cafe;
use App\Models\Branch;
use App\Models\BranchHour;
use App\Models\Amenity;
use App\Models\GameMatch;
use App\Models\Team;
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
            'day_of_week' => 1,
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
        
        $branch->amenities()->create(['name' => 'WiFi']);
        $branch->amenities()->create(['name' => 'Parking']);

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
    public function branch_detail_current_matches_lists_every_live_and_upcoming_match()
    {
        $branch = Branch::factory()->create(['cafe_id' => Cafe::factory()->create()->id]);
        $make = fn (array $attrs) => GameMatch::factory()->create($attrs + [
            'branch_id' => $branch->id, 'is_published' => true, 'status' => 'upcoming',
            'match_date' => now()->addDays(3)->toDateString(), 'kick_off' => '17:00',
        ]);

        $later = $make(['match_date' => now()->addDays(10)->toDateString()]);
        $soon = $make(['match_date' => now()->addDays(2)->toDateString()]);
        $live = $make(['status' => 'live', 'match_date' => now()->toDateString()]);
        $moreUpcoming = GameMatch::factory()->count(5)->create([
            'branch_id' => $branch->id, 'is_published' => true, 'status' => 'upcoming',
            'match_date' => now()->addDays(5)->toDateString(), 'kick_off' => '17:00',
        ]);

        // None of these belong in the list.
        $make(['status' => 'cancelled']);
        $make(['status' => 'finished']);
        $make(['is_published' => false]);
        $make(['match_date' => now()->subDay()->toDateString()]); // past, status never updated

        $ids = collect($this->getJson("/api/v1/branches/{$branch->id}")->assertOk()->json('data.current_matches'))->pluck('id');

        $this->assertSame(
            [$live->id, $soon->id, ...$moreUpcoming->pluck('id')->all(), $later->id],
            $ids->all()
        );
    }

    /** @test */
    public function match_team_logos_fall_back_to_a_placeholder_and_current_matches_are_static()
    {
        $branch = Branch::factory()->create(['cafe_id' => Cafe::factory()->create()->id]);
        $noLogo = Team::factory()->create(['logo' => null, 'short_name' => 'FCB']);
        $blankLogo = Team::factory()->create(['logo' => '']);
        GameMatch::factory()->create([
            'branch_id' => $branch->id, 'is_published' => true, 'status' => 'upcoming',
            'match_date' => now()->addDay()->toDateString(),
            'home_team_id' => $noLogo->id, 'away_team_id' => $blankLogo->id,
        ]);

        $match = $this->getJson("/api/v1/branches/{$branch->id}/matches")->assertOk()->json('data.0');

        $this->assertStringStartsWith('https://ui-avatars.com/api/?name=FCB', $match['home_team']['logo']);
        $this->assertStringStartsWith('https://ui-avatars.com/api/?name=', $match['away_team']['logo']);

        // current_matches on the branch detail returns an empty logo for now.
        $current = $this->getJson("/api/v1/branches/{$branch->id}")->assertOk()->json('data.current_matches.0');
        $this->assertSame('', $current['home_team']['logo']);
        $this->assertSame('', $current['away_team']['logo']);
    }

    /** @test */
    public function team_short_names_in_current_matches_have_at_least_two_letters()
    {
        $branch = Branch::factory()->create(['cafe_id' => Cafe::factory()->create()->id]);
        GameMatch::factory()->create([
            'branch_id' => $branch->id, 'is_published' => true, 'status' => 'upcoming',
            'match_date' => now()->addDay()->toDateString(),
            'home_team_id' => Team::factory()->create(['name' => 'Arsenal F.C.', 'short_name' => 'A'])->id,
            'away_team_id' => Team::factory()->create(['name' => 'Hull City A.F.C.', 'short_name' => 'HC'])->id,
        ]);

        $match = $this->getJson("/api/v1/branches/{$branch->id}")->assertOk()->json('data.current_matches.0');

        $this->assertSame('AR', $match['home_team']['short_name']);
        $this->assertSame('HC', $match['away_team']['short_name']); // already fine, untouched

        $this->assertSame('CO', (new Team(['name' => 'F.C. Copenhagen', 'short_name' => 'C']))->short_name);
        $this->assertSame('DR', (new Team(['name' => 'Drumcondra F.C.', 'short_name' => null]))->short_name);
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

