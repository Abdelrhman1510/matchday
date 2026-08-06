<?php

namespace Tests\Feature\Bookings;

use App\Events\BookingCreated;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\GameMatch;
use App\Models\LoyaltyCard;
use App\Models\Seat;
use App\Models\SeatingSection;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingRobustnessTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveSubscription(Cafe $cafe): void
    {
        $plan = SubscriptionPlan::factory()->create([
            'max_branches' => 10, 'max_matches_per_month' => 100,
            'max_bookings_per_month' => 500, 'max_staff_members' => 20, 'max_offers' => 20,
        ]);
        CafeSubscription::factory()->create([
            'cafe_id' => $cafe->id, 'plan_id' => $plan->id,
            'status' => 'active', 'expires_at' => now()->addMonth(),
        ]);
    }

    /** @return array{0: GameMatch, 1: SeatingSection} */
    private function bookableMatch(int $seatsAvailable = 20): array
    {
        $cafe = Cafe::factory()->create();
        $this->createActiveSubscription($cafe);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id, 'is_published' => true,
            'status' => 'upcoming', 'seats_available' => $seatsAvailable,
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        return [$match, $section];
    }

    /** @test */
    public function booking_allows_more_than_8_seats()
    {
        [$match, $section] = $this->bookableMatch(20);
        $seats = Seat::factory()->count(10)->create(['section_id' => $section->id, 'is_available' => true]);
        $user = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => $seats->pluck('id')->all(),
            'guests_count' => 10,
        ])->assertStatus(201);
    }

    /** @test */
    public function booking_commits_even_if_post_commit_notification_throws()
    {
        Notification::fake();
        // Simulate a synchronous notification/listener failure after the booking is created.
        Event::listen(BookingCreated::class, function () {
            throw new \RuntimeException('notification gateway down');
        });

        [$match, $section] = $this->bookableMatch(5);
        $seat = Seat::factory()->create(['section_id' => $section->id, 'is_available' => true]);
        $user = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => [$seat->id],
        ])->assertStatus(201);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'match_id' => $match->id,
            'status' => 'pending',
        ]);
    }
}
