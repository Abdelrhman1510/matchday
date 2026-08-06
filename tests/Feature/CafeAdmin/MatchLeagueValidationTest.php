<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\Branch;
use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\SubscriptionPlan;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatchLeagueValidationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Branch} */
    private function ownerWithBranch(): array
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $plan = SubscriptionPlan::factory()->create([
            'max_branches' => 10, 'max_matches_per_month' => 100,
            'max_bookings_per_month' => 500, 'max_staff_members' => 20, 'max_offers' => 20,
        ]);
        CafeSubscription::factory()->create([
            'cafe_id' => $cafe->id, 'plan_id' => $plan->id,
            'status' => 'active', 'expires_at' => now()->addMonth(),
        ]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        return [$owner, $branch];
    }

    /** @test */
    public function match_creation_rejects_teams_not_in_same_league()
    {
        [$owner, $branch] = $this->ownerWithBranch();
        $spanish = Team::factory()->create(['league' => 'Spanish League']);
        $egyptian = Team::factory()->create(['league' => 'Egyptian Premier League']);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/admin/branches/{$branch->id}/matches", [
            'home_team_id' => $spanish->id,
            'away_team_id' => $egyptian->id,
            'match_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
        ])->assertStatus(422)
            ->assertJsonPath('errors.away_team_id.0', 'Both teams must belong to the same league as the home team.');
    }

    /** @test */
    public function match_creation_rejects_teams_not_in_selected_league()
    {
        [$owner, $branch] = $this->ownerWithBranch();
        $spanish1 = Team::factory()->create(['league' => 'Spanish League']);
        $spanish2 = Team::factory()->create(['league' => 'Spanish League']);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/admin/branches/{$branch->id}/matches", [
            'home_team_id' => $spanish1->id,
            'away_team_id' => $spanish2->id,
            'match_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'league' => 'English Premier League',
        ])->assertStatus(422)
            ->assertJsonPath('errors.home_team_id.0', 'Both teams must belong to the selected league.');
    }

    /** @test */
    public function match_creation_allows_teams_in_the_selected_league()
    {
        [$owner, $branch] = $this->ownerWithBranch();
        $home = Team::factory()->create(['league' => 'Spanish League']);
        $away = Team::factory()->create(['league' => 'Spanish League']);
        Sanctum::actingAs($owner);

        $this->postJson("/api/v1/admin/branches/{$branch->id}/matches", [
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'match_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'league' => 'Spanish League',
        ])->assertStatus(201);
    }
}
