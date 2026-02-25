<?php

namespace Tests\Feature\Bookings;

use App\Models\User;
use App\Models\GameMatch;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\Booking;
use App\Models\Seat;
use App\Models\SeatingSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManageBookingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_lists_user_own_bookings()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        
        Booking::factory()->count(3)->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
        ]);
        
        // Other user's bookings
        Booking::factory()->count(2)->create(['match_id' => $match->id]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/bookings');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'booking_code', 'match', 'status'],
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_filters_bookings_by_upcoming_tab()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        $upcomingMatch = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'match_date' => now()->addDays(5),
        ]);
        $pastMatch = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'match_date' => now()->subDays(5),
        ]);
        
        Booking::factory()->count(2)->create([
            'user_id' => $user->id,
            'match_id' => $upcomingMatch->id,
            'status' => 'confirmed',
        ]);
        Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $pastMatch->id,
            'status' => 'completed',
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/bookings?tab=upcoming');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_filters_bookings_by_past_tab()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        
        $pastMatch = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'match_date' => now()->subDays(5),
        ]);
        
        Booking::factory()->count(3)->create([
            'user_id' => $user->id,
            'match_id' => $pastMatch->id,
            'status' => 'completed',
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/bookings?tab=past');

        $response->assertStatus(200);

        $this->assertCount(3, $response->json('data'));
    }

    /** @test */
    public function it_filters_bookings_by_cancelled_tab()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        
        Booking::factory()->count(2)->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'status' => 'cancelled',
        ]);
        Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'status' => 'confirmed',
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/bookings?tab=cancelled');

        $response->assertStatus(200);

        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_views_booking_details()
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

        $response = $this->getJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'booking_code',
                    'qr_code',
                    'match',
                    'seats',
                    'status',
                    'total_price',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_cancels_booking_and_releases_seats()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'seats_available' => 5,
            'match_date' => now()->addDays(7)->format('Y-m-d'),
            'kick_off' => '20:00:00',
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat1 = Seat::factory()->create(['section_id' => $section->id, 'is_available' => false]);
        $seat2 = Seat::factory()->create(['section_id' => $section->id, 'is_available' => false]);
        
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'branch_id' => $branch->id,
            'status' => 'confirmed',
        ]);
        $booking->seats()->attach([$seat1->id, $seat2->id]);
        
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/bookings/{$booking->id}/cancel");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);

        $match->refresh();
        $this->assertEquals(7, $match->seats_available);

        $seat1->refresh();
        $seat2->refresh();
        $this->assertTrue($seat1->is_available);
        $this->assertTrue($seat2->is_available);
    }

    /** @test */
    public function it_returns_403_for_other_user_booking()
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

        $response = $this->getJson("/api/v1/bookings/{$booking->id}");

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_booking()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/bookings/99999');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_401_for_unauthenticated_booking_list()
    {
        $response = $this->getJson('/api/v1/bookings');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_cancelling_already_cancelled_booking()
    {
        $user = User::factory()->create();
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create(['branch_id' => $branch->id]);
        
        $booking = Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'status' => 'cancelled',
        ]);
        
        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/bookings/{$booking->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }
}
