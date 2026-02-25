<?php

namespace Tests\Feature\Bookings;

use App\Models\User;
use App\Models\GameMatch;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\Booking;
use App\Models\BookingPlayer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingPlayerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_adds_player_to_booking()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/players", [
            'name' => 'John Doe',
            'jersey_number' => 10,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'name', 'jersey_number'],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'John Doe',
                    'jersey_number' => 10,
                ],
            ]);

        $this->assertDatabaseHas('booking_players', [
            'booking_id' => $booking->id,
            'name' => 'John Doe',
            'jersey_number' => 10,
        ]);
    }

    /** @test */
    public function it_lists_players_for_booking()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
        
        BookingPlayer::factory()->count(3)->create(['booking_id' => $booking->id]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/bookings/{$booking->id}/players");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'jersey_number'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_removes_player_from_booking()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
        $player = BookingPlayer::factory()->create(['booking_id' => $booking->id]);
        
        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/bookings/{$booking->id}/players/{$player->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('booking_players', [
            'id' => $player->id,
        ]);
    }

    /** @test */
    public function it_returns_403_for_other_user_adding_player()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user1->id,
            'match_id' => $match->id,
        ]);
        
        Sanctum::actingAs($user2);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/players", [
            'name' => 'John Doe',
            'jersey_number' => 10,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_missing_player_name()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/players", [
            'jersey_number' => 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['name'],
            ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_player_add()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create(['match_id' => $match->id]);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/players", [
            'name' => 'John Doe',
            'jersey_number' => 10,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_booking()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/bookings/99999/players', [
            'name' => 'John Doe',
            'jersey_number' => 10,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_allows_optional_jersey_number()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/players", [
            'name' => 'John Doe',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'John Doe',
                ],
            ]);
    }
}
