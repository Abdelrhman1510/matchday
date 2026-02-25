<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Services\LoyaltyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AwardBookingPoints implements ShouldQueue
{
    use InteractsWithQueue;

    protected LoyaltyService $loyaltyService;

    /**
     * Create the event listener.
     */
    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * Handle the event.
     */
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking;
        $user = $booking->user;

        // Load necessary relationships
        $booking->load(['match.homeTeam', 'match.awayTeam']);

        // Calculate points based on booking amount
        // Base: 1 point per SAR spent
        $basePoints = (int) floor($booking->total_amount);

        // Tier multiplier
        $tier = $user->loyaltyCard?->tier ?? 'bronze';
        $multiplier = match ($tier) {
            'silver' => 1.5,
            'gold' => 2,
            'platinum' => 3,
            default => 1,
        };

        $points = (int) floor($basePoints * $multiplier);

        // Award points
        $description = "Booking for {$booking->match->homeTeam->name} vs {$booking->match->awayTeam->name}";

        try {
            $this->loyaltyService->awardPoints($user, $points, $description, $booking->id);
        } catch (\Exception $e) {
            // Log error but don't fail the booking
            logger()->error('Failed to award loyalty points: ' . $e->getMessage(), [
                'booking_id' => $booking->id,
                'user_id' => $user->id,
            ]);
        }
    }
}
