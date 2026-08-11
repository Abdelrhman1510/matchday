<?php

namespace Tests\Feature\Bookings;

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
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class Bug091BookingTest extends TestCase
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

    /** A match whose booking period is ACTIVE right now. */
    private function bookableMatch(): array
    {
        $cafe = Cafe::factory()->create();
        $this->createActiveSubscription($cafe);
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $match = GameMatch::factory()->create([
            'branch_id' => $branch->id,
            'is_published' => true,
            'status' => 'upcoming',
            'seats_available' => 20,
            'booking_opens_at' => now()->subHour(),   // opened an hour ago
            'booking_closes_at' => now()->addHour(),   // closes in an hour
        ]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        return [$match, $section];
    }

    /** @test */
    public function match_details_reports_can_book_true_during_an_active_window()
    {
        [$match, $section] = $this->bookableMatch();
        Seat::factory()->create(['section_id' => $section->id, 'is_available' => true]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/matches/{$match->id}")
            ->assertOk()
            ->assertJsonPath('data.can_book', true);
    }

    /** @test */
    public function booking_succeeds_when_guests_count_is_sent_as_a_string()
    {
        // The Flutter client may serialize guests_count as a JSON string ("2").
        // It passes the integer rule but a strict !== against the seat count
        // would falsely reject the booking (BUG-091).
        [$match, $section] = $this->bookableMatch();
        $seats = Seat::factory()->count(2)->create(['section_id' => $section->id, 'is_available' => true]);
        $user = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => $seats->pluck('id')->all(),
            'guests_count' => '2', // string on purpose
        ])->assertStatus(201);
    }

    /** @test */
    public function a_match_created_without_explicit_seats_defaults_to_the_branch_capacity()
    {
        // BUG-091 root cause: createMatch used to default seats_available to 0,
        // making the match permanently unbookable. It should instead fall back
        // to the branch's real seat count.
        $cafe = Cafe::factory()->create();
        $branch = Branch::factory()->create(['cafe_id' => $cafe->id]);
        $section = SeatingSection::factory()->create(['branch_id' => $branch->id]);
        Seat::factory()->count(12)->create(['section_id' => $section->id, 'is_available' => true]);

        $match = app(\App\Services\MatchAdminService::class)->createMatch($branch->id, [
            'home_team_id' => \App\Models\Team::factory()->create()->id,
            'away_team_id' => \App\Models\Team::factory()->create()->id,
            'match_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
            // no seats_available provided
        ]);

        $this->assertSame(12, (int) $match->seats_available);
    }

    /** @test */
    public function booking_succeeds_when_guests_count_is_omitted()
    {
        [$match, $section] = $this->bookableMatch();
        $seat = Seat::factory()->create(['section_id' => $section->id, 'is_available' => true]);
        $user = User::factory()->create();
        LoyaltyCard::factory()->create(['user_id' => $user->id]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/bookings', [
            'match_id' => $match->id,
            'seat_ids' => [$seat->id],
        ])->assertStatus(201);
    }
}
