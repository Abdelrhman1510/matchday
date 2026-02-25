<?php

namespace Tests\Feature\Bookings;

use App\Models\User;
use App\Models\GameMatch;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\Booking;
use App\Models\ChatRoom;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RebookAndFanRoomTest extends TestCase
{
    use RefreshDatabase;

    // ========================
    // REBOOK TESTS
    // ========================

    /** @test */
    public function it_suggests_next_match_with_same_teams()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $teamA = Team::factory()->create(['name' => 'Al Hilal']);
        $teamB = Team::factory()->create(['name' => 'Al Nassr']);

        $pastMatch = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'match_date' => now()->subDays(3),
            'status' => 'finished',
        ]);

        $upcomingMatch = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'match_date' => now()->addDays(7),
            'status' => 'upcoming',
            'is_published' => true,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $pastMatch->id,
            'branch_id' => $branch->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/rebook");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'suggested_match' => [
                        'id',
                        'home_team',
                        'away_team',
                        'match_date',
                        'kick_off',
                    ],
                    'branch',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'suggested_match' => [
                        'id' => $upcomingMatch->id,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_returns_similar_matches_when_no_exact_rematch()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $teamA = Team::factory()->create(['name' => 'Al Hilal']);
        $teamB = Team::factory()->create(['name' => 'Al Nassr']);
        $teamC = Team::factory()->create(['name' => 'Al Ahli']);

        $pastMatch = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'match_date' => now()->subDays(3),
            'status' => 'finished',
        ]);

        // Match with team A but different opponent
        $similarMatch = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamC->id,
            'match_date' => now()->addDays(5),
            'status' => 'upcoming',
            'is_published' => true,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $pastMatch->id,
            'branch_id' => $branch->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/rebook");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'suggested_match' => null,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'similar_matches',
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('data.similar_matches')));
    }

    /** @test */
    public function it_returns_404_for_rebook_nonexistent_booking()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/bookings/99999/rebook');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_prevents_rebook_for_other_users_booking()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);

        $booking = Booking::factory()->create([
            'user_id' => $otherUser->id,
            'match_id' => $match->id,
            'branch_id' => $branch->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/rebook");

        $response->assertStatus(404);
    }

    /** @test */
    public function it_requires_auth_for_rebook()
    {
        $response = $this->postJson('/api/v1/bookings/1/rebook');

        $response->assertStatus(401);
    }

    // ========================
    // ENTER FAN ROOM TESTS
    // ========================

    /** @test */
    public function it_enters_fan_room_for_confirmed_booking_with_live_match()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'live',
            'is_live' => true,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'status' => 'confirmed',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/enter-fan-room");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['chat_room_id'],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('chat_rooms', [
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'type' => 'cafe',
        ]);
    }

    /** @test */
    public function it_enters_fan_room_for_checked_in_booking()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'live',
            'is_live' => true,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'status' => 'checked_in',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/enter-fan-room");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    /** @test */
    public function it_returns_existing_chat_room_on_second_entry()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'live',
            'is_live' => true,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'status' => 'confirmed',
        ]);

        Sanctum::actingAs($user);

        $response1 = $this->postJson("/api/v1/bookings/{$booking->id}/enter-fan-room");
        $response2 = $this->postJson("/api/v1/bookings/{$booking->id}/enter-fan-room");

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $this->assertEquals(
            $response1->json('data.chat_room_id'),
            $response2->json('data.chat_room_id')
        );

        $this->assertEquals(1, ChatRoom::where('match_id', $match->id)->where('branch_id', $branch->id)->count());
    }

    /** @test */
    public function it_rejects_fan_room_for_pending_booking()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_live' => true,
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/enter-fan-room");

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_rejects_fan_room_when_match_not_live()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_live' => false,
            'status' => 'upcoming',
        ]);

        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'status' => 'confirmed',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/enter-fan-room");

        $response->assertStatus(403)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function it_returns_404_for_fan_room_nonexistent_booking()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/bookings/99999/enter-fan-room');

        $response->assertStatus(404);
    }

    /** @test */
    public function it_requires_auth_for_fan_room()
    {
        $response = $this->postJson('/api/v1/bookings/1/enter-fan-room');

        $response->assertStatus(401);
    }
}
