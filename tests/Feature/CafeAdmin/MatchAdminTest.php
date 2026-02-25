<?php

namespace Tests\Feature\CafeAdmin;

use App\Models\User;
use App\Models\Cafe;
use App\Models\Branch;
use App\Models\CafeSubscription;
use App\Models\GameMatch;
use App\Models\SubscriptionPlan;
use App\Models\Team;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatchAdminTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveSubscription(Cafe $cafe): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'max_branches' => 10,
            'max_matches_per_month' => 100,
            'max_bookings_per_month' => 500,
            'max_staff_members' => 20,
            'max_offers' => 20,
        ]);
        CafeSubscription::factory()->create([
            'cafe_id' => $cafe->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);
    }

    /** @test */
    public function it_creates_match_as_unpublished()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $this->createActiveSubscription($cafe);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/matches", [
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'match_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'ticket_price' => 50,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'home_team', 'away_team', 'status'],
            ])
            ->assertJson([
                'success' => true,
                'data' => ['status' => 'draft'],
            ]);

        $this->assertDatabaseHas('game_matches', [
            'branch_id' => $branch->id,
            'is_published' => false,
        ]);
    }

    /** @test */
    public function it_publishes_match()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => false,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/matches/{$match->id}/publish");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('game_matches', [
            'id' => $match->id,
            'is_published' => true,
        ]);
    }

    /** @test */
    public function it_updates_match_score()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/matches/{$match->id}/score", [
            'home_score' => 2,
            'away_score' => 1,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'home_score' => 2,
                    'away_score' => 1,
                ],
            ]);
    }

    /** @test */
    public function it_transitions_match_status()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
            'match_date' => now()->addHour(),
        ]);
        Sanctum::actingAs($owner);

        // Start match (set to live)
        $response = $this->putJson("/api/v1/admin/matches/{$match->id}/start");

        $response->assertStatus(200);

        $match->refresh();
        $this->assertTrue($match->is_live);

        // End match
        $response = $this->putJson("/api/v1/admin/matches/{$match->id}/end");

        $response->assertStatus(200);

        $match->refresh();
        $this->assertEquals('completed', $match->status);
    }

    /** @test */
    public function it_cancels_match_and_processes_refunds()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);

        // Create bookings with payments
        $booking = Booking::factory()->create([
            'match_id' => $match->id,
            'status' => 'confirmed',
        ]);
        $payment = Payment::factory()->create([
            'booking_id' => $booking->id,
            'status' => 'completed',
            'amount' => 100,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/matches/{$match->id}/cancel", [
            'reason' => 'Weather conditions',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('game_matches', [
            'id' => $match->id,
            'status' => 'cancelled',
        ]);

        // Verify refunds processed
        $payment->refresh();
        $this->assertEquals('refunded', $payment->status);
    }

    /** @test */
    public function it_lists_branch_matches()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        GameMatch::factory()->count(5)->create(['branch_id' => $branch->id]);
        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/v1/admin/branches/{$branch->id}/matches");

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

        $this->assertCount(5, $response->json('data'));
    }

    /** @test */
    public function it_returns_403_for_fan_creating_match()
    {
        $fan = User::factory()->fan()->create();
        $branch = Branch::factory()->create();
        Sanctum::actingAs($fan);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/matches", [
            'home_team_id' => 1,
            'away_team_id' => 2,
            'match_date' => now()->addDays(5),
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_same_home_and_away_team()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $this->createActiveSubscription($cafe);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $team = Team::factory()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/matches", [
            'home_team_id' => $team->id,
            'away_team_id' => $team->id,
            'match_date' => now()->addDays(5),
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
            ]);
    }

    /** @test */
    public function it_returns_422_for_past_match_date()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $this->createActiveSubscription($cafe);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $homeTeam = Team::factory()->create();
        $awayTeam = Team::factory()->create();
        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/v1/admin/branches/{$branch->id}/matches", [
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'match_date' => now()->subDays(1)->format('Y-m-d H:i:s'),
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['match_date'],
            ]);
    }

    /** @test */
    public function it_updates_match_details()
    {
        $owner = User::factory()->cafeOwner()->create();
        $cafe = Cafe::factory()->create(['owner_id' => $owner->id]);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => false,
        ]);
        Sanctum::actingAs($owner);

        $response = $this->putJson("/api/v1/admin/matches/{$match->id}", [
            'match_date' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'ticket_price' => 75,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'ticket_price' => 75,
                ],
            ]);
    }
}

