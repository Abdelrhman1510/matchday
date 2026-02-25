<?php

namespace Tests\Feature\Bookings;

use App\Models\User;
use App\Models\GameMatch;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\Seat;
use App\Models\SeatingSection;
use App\Models\SubscriptionPlan;
use App\Models\Booking;
use App\Models\LoyaltyCard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateBookingTest extends TestCase
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
    public function it_creates_booking_successfully_with_qr_code()
    {
        $user = User::factory()->create();
        $loyaltyCard = LoyaltyCard::factory()->create(['user_id' => $user->id, 'points' => 0]);
        $cafe = Cafe::factory()->create();
        $this->createActiveSubscription($cafe);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
            'seats_available' => 10,
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create([
            'section_id' => $section->id,
            'is_available' => true,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => [$seat->id],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'booking_code',
                    'qr_code',
                    'match',
                    'seats',
                    'total_price',
                ],
            ])
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'match_id' => $match->id,
            'status' => 'confirmed',
        ]);

        $this->assertNotNull($response->json('data.qr_code'));
    }

    /** @test */
    public function it_decrements_seats_available()
    {
        $user = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        $cafe = Cafe::factory()->create();
        $this->createActiveSubscription($cafe);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
            'seats_available' => 10,
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat1 = Seat::factory()->create(['section_id' => $section->id, 'is_available' => true]);
        $seat2 = Seat::factory()->create(['section_id' => $section->id, 'is_available' => true]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => [$seat1->id, $seat2->id],
        ]);

        $match->refresh();
        $this->assertEquals(8, $match->seats_available);
    }

    /** @test */
    public function it_awards_loyalty_points()
    {
        $user = User::factory()->create();
        $loyaltyCard = LoyaltyCard::factory()->create(['user_id' => $user->id, 'points' => 50]);
        $cafe = Cafe::factory()->create();
        $this->createActiveSubscription($cafe);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
            'seats_available' => 10,
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['section_id' => $section->id, 'is_available' => true]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => [$seat->id],
        ]);

        $loyaltyCard->refresh();
        $this->assertGreaterThan(50, $loyaltyCard->points);
    }

    /** @test */
    public function it_returns_422_for_already_booked_seats()
    {
        $user = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create([
            'section_id' => $section->id,
            'is_available' => false,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => [$seat->id],
        ]);

        $response->assertStatus(422)
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
    public function it_returns_422_for_duplicate_booking()
    {
        $user = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['section_id' => $section->id, 'is_available' => true]);
        Sanctum::actingAs($user);

        // Create first booking
        Booking::factory()->create([
            'user_id' => $user->id,
            'match_id' => $match->id,
            'status' => 'confirmed',
        ]);

        // Try to create duplicate
        $response = $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => [$seat->id],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_unpublished_match()
    {
        $user = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => false,
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['section_id' => $section->id, 'is_available' => true]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => [$seat->id],
        ]);

        $response->assertStatus(422)
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
    public function it_returns_401_for_unauthenticated_booking()
    {
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['section_id' => $section->id]);

        $response = $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => [$seat->id],
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function it_returns_422_for_missing_seat_ids()
    {
        $user = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => ['seat_ids'],
            ]);
    }

    /** @test */
    public function it_generates_unique_booking_code()
    {
        $user = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        $cafe = Cafe::factory()->create();
        $this->createActiveSubscription($cafe);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        $seat = Seat::factory()->create(['section_id' => $section->id, 'is_available' => true]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => [$seat->id],
        ]);

        $bookingCode = $response->json('data.booking_code');
        $this->assertMatchesRegularExpression('/^BOOK-[A-Z0-9]{6}$/', $bookingCode);
    }
}

