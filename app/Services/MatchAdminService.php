<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cafe;
use App\Models\GameMatch;
use App\Events\MatchPublished;
use App\Events\MatchScoreUpdated;
use App\Events\MatchCancelled;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\MatchReminderNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class MatchAdminService
{
    /**
     * List matches for a cafe's branches with filters
     */
    public function listMatches(Cafe $cafe, array $filters = []): array
    {
        $branchIds = $cafe->branches()->pluck('id');

        $query = GameMatch::whereIn('branch_id', $branchIds)
            ->with(['homeTeam', 'awayTeam', 'branch'])
            ->withCount([
                'bookings as booking_count' => function ($q) {
                    $q->whereIn('status', ['confirmed', 'pending', 'checked_in']);
                },
            ])
            ->withSum([
                'bookings as revenue' => function ($q) {
                    $q->whereIn('status', ['confirmed', 'checked_in']);
                },
            ], 'total_amount');

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by branch
        if (!empty($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        $matches = $query->orderByDesc('match_date')
            ->orderByDesc('kick_off')
            ->paginate($filters['per_page'] ?? 15);

        return [
            'matches' => $matches,
        ];
    }

    /**
     * Create a new match (unpublished)
     */
    public function createMatch(int $branchId, array $data): GameMatch
    {
        return DB::transaction(function () use ($branchId, $data) {
            $match = GameMatch::create([
                'branch_id' => $branchId,
                'home_team_id' => $data['home_team_id'],
                'away_team_id' => $data['away_team_id'],
                'league' => $data['league'] ?? 'TBD',
                'match_date' => $data['match_date'],
                'kick_off' => $data['kick_off'] ?? null,
                'seats_available' => $data['seats_available'] ?? 0,
                'price_per_seat' => $data['price_per_seat'] ?? ($data['ticket_price'] ?? 0),
                'ticket_price' => $data['ticket_price'] ?? ($data['price_per_seat'] ?? 0),
                'duration_minutes' => $data['duration_minutes'] ?? 90,
                'booking_opens_at' => $data['booking_opens_at'] ?? null,
                'booking_closes_at' => $data['booking_closes_at'] ?? null,
                'field_name' => $data['field_name'] ?? null,
                'venue_name' => $data['venue_name'] ?? null,
                'is_published' => false,
                'is_live' => false,
                'status' => 'draft',
                'home_score' => null,
                'away_score' => null,
                'total_revenue' => 0,
            ]);

            $match->load(['homeTeam', 'awayTeam', 'branch.cafe']);

            return $match;
        });
    }

    /**
     * Get match detail with full booking stats
     */
    public function getMatchDetail(GameMatch $match): array
    {
        $match->load(['homeTeam', 'awayTeam', 'branch.cafe', 'branch.seatingSections.seats']);

        $cacheKey = "match_admin_detail_{$match->id}";

        $stats = Cache::remember($cacheKey, 120, function () use ($match) {
            $bookings = $match->bookings()
                ->whereIn('status', ['confirmed', 'pending', 'checked_in'])
                ->get();

            $totalBooked = $bookings->sum('guests_count');
            $capacity = $match->seats_available;
            $checkedIn = $bookings->where('status', 'checked_in')->sum('guests_count');
            $pending = $bookings->where('status', 'pending')->sum('guests_count');
            $confirmed = $bookings->whereIn('status', ['confirmed', 'checked_in'])->sum('guests_count');

            $totalRevenue = $bookings->whereIn('status', ['confirmed', 'checked_in'])->sum('total_amount');
            $bookingsCount = $bookings->whereIn('status', ['confirmed', 'checked_in'])->count();

            return [
                'booking_stats' => [
                    'total_booked' => $totalBooked,
                    'capacity_pct' => $capacity > 0 ? round(($totalBooked / $capacity) * 100, 1) : 0,
                    'checked_in' => $checkedIn,
                    'arrived_pct' => $totalBooked > 0 ? round(($checkedIn / $totalBooked) * 100, 1) : 0,
                    'pending' => $pending,
                    'pending_pct' => $totalBooked > 0 ? round(($pending / $totalBooked) * 100, 1) : 0,
                    'available' => max(0, $capacity - $totalBooked),
                    'remaining_pct' => $capacity > 0 ? round((max(0, $capacity - $totalBooked) / $capacity) * 100, 1) : 0,
                ],
                'revenue' => [
                    'total' => (float) $totalRevenue,
                    'bookings_count' => $bookingsCount,
                    'average_per_booking' => $bookingsCount > 0 ? round($totalRevenue / $bookingsCount, 2) : 0,
                ],
            ];
        });

        return [
            'match' => $match,
            'booking_stats' => $stats['booking_stats'],
            'revenue' => $stats['revenue'],
            'booking_timing' => [
                'opens_at' => $match->booking_opens_at?->toIso8601String(),
                'closes_at' => $match->booking_closes_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Update a match (only if upcoming)
     */
    public function updateMatch(GameMatch $match, array $data): array
    {
        if (!in_array($match->status, ['upcoming', 'draft'])) {
            return [
                'success' => false,
                'message' => 'Only upcoming matches can be edited.',
            ];
        }

        $match->update(array_filter([
            'home_team_id' => $data['home_team_id'] ?? null,
            'away_team_id' => $data['away_team_id'] ?? null,
            'league' => $data['league'] ?? null,
            'match_date' => $data['match_date'] ?? null,
            'kick_off' => $data['kick_off'] ?? null,
            'seats_available' => $data['seats_available'] ?? null,
            'price_per_seat' => $data['price_per_seat'] ?? null,
            'ticket_price' => $data['ticket_price'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'booking_opens_at' => array_key_exists('booking_opens_at', $data) ? $data['booking_opens_at'] : null,
            'booking_closes_at' => array_key_exists('booking_closes_at', $data) ? $data['booking_closes_at'] : null,
            'field_name' => array_key_exists('field_name', $data) ? $data['field_name'] : null,
            'venue_name' => array_key_exists('venue_name', $data) ? $data['venue_name'] : null,
        ], fn($v) => $v !== null));

        // Bust cache
        Cache::forget("match_admin_detail_{$match->id}");

        $match->load(['homeTeam', 'awayTeam', 'branch.cafe']);

        return [
            'success' => true,
            'match' => $match->fresh(),
        ];
    }

    /**
     * Cancel match: notify bookers, refund confirmed bookings, release seats
     */
    public function cancelMatch(GameMatch $match): array
    {
        if ($match->status === 'cancelled') {
            return [
                'success' => false,
                'message' => 'Match is already cancelled.',
            ];
        }

        if ($match->status === 'finished') {
            return [
                'success' => false,
                'message' => 'Cannot cancel a finished match.',
            ];
        }

        return DB::transaction(function () use ($match) {
            // Get active bookings before cancelling
            $activeBookings = $match->bookings()
                ->with(['user', 'seats', 'branch.cafe'])
                ->whereIn('status', ['confirmed', 'pending', 'checked_in'])
                ->get();

            $refundedCount = 0;
            $notifiedUsers = [];

            foreach ($activeBookings as $booking) {
                // Release seats
                foreach ($booking->seats as $seat) {
                    $seat->update(['is_available' => true]);
                }

                // Refund confirmed bookings
                if (in_array($booking->status, ['confirmed', 'checked_in'])) {
                    $refundedCount++;
                }

                // Cancel the booking
                $booking->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                // Notify the user
                if ($booking->user && !in_array($booking->user_id, $notifiedUsers)) {
                    $booking->user->notify(new BookingCancelledNotification($booking, 'Match cancelled by the cafe.'));
                    $notifiedUsers[] = $booking->user_id;
                }
            }

            // Cancel the match
            $match->update([
                'status' => 'cancelled',
            ]);

            // Fire event
            event(new MatchCancelled($match, $activeBookings->count()));

            // Bust cache
            Cache::forget("match_admin_detail_{$match->id}");

            return [
                'success' => true,
                'message' => 'Match cancelled successfully.',
                'bookings_cancelled' => $activeBookings->count(),
                'bookings_refunded' => $refundedCount,
                'users_notified' => count($notifiedUsers),
            ];
        });
    }

    /**
     * Publish a match
     */
    public function publishMatch(GameMatch $match): array
    {
        if ($match->is_published) {
            return [
                'success' => false,
                'message' => 'Match is already published.',
            ];
        }

        $match->update(['is_published' => true]);

        event(new MatchPublished($match));

        Cache::forget("match_admin_detail_{$match->id}");

        return [
            'success' => true,
            'message' => 'Match published successfully.',
            'match' => $match->fresh()->load(['homeTeam', 'awayTeam', 'branch.cafe']),
        ];
    }

    /**
     * Update match score
     */
    public function updateScore(GameMatch $match, int $homeScore, int $awayScore): array
    {
        if (in_array($match->status, ['cancelled'])) {
            return [
                'success' => false,
                'message' => 'Score cannot be updated for cancelled matches.',
            ];
        }

        $match->update([
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ]);

        $match->load(['homeTeam', 'awayTeam']);

        // Broadcast score update
        event(new MatchScoreUpdated($match));

        Cache::forget("match_admin_detail_{$match->id}");

        return [
            'success' => true,
            'message' => 'Score updated successfully.',
            'match' => $match->fresh()->load(['homeTeam', 'awayTeam', 'branch.cafe']),
        ];
    }

    /**
     * Update match status with transition validation
     */
    public function updateStatus(GameMatch $match, string $newStatus): array
    {
        $validTransitions = [
            'upcoming' => 'live',
            'live' => 'finished',
        ];

        $currentStatus = $match->status;

        if (!isset($validTransitions[$currentStatus])) {
            return [
                'success' => false,
                'message' => "Cannot transition from '{$currentStatus}'. Match status is final.",
            ];
        }

        if ($validTransitions[$currentStatus] !== $newStatus) {
            $expected = $validTransitions[$currentStatus];
            return [
                'success' => false,
                'message' => "Invalid transition: '{$currentStatus}' → '{$newStatus}'. Expected: '{$currentStatus}' → '{$expected}'.",
            ];
        }

        $match->update(['status' => $newStatus]);

        // If going live, set score to 0-0 if not set
        if ($newStatus === 'live' && $match->home_score === null) {
            $match->update([
                'home_score' => 0,
                'away_score' => 0,
            ]);
        }

        Cache::forget("match_admin_detail_{$match->id}");

        return [
            'success' => true,
            'message' => "Match status changed to '{$newStatus}'.",
            'match' => $match->fresh()->load(['homeTeam', 'awayTeam', 'branch.cafe']),
        ];
    }

    /**
     * Send reminder notification to all bookers (rate limited: 1 per match per day)
     */
    public function sendReminder(GameMatch $match): array
    {
        // Rate limit: 1 reminder per match per day
        if ($match->last_reminder_sent_at && $match->last_reminder_sent_at->isToday()) {
            return [
                'success' => false,
                'message' => 'A reminder has already been sent for this match today. Try again tomorrow.',
                'last_sent_at' => $match->last_reminder_sent_at->toIso8601String(),
            ];
        }

        $match->load(['homeTeam', 'awayTeam']);

        // Get unique users with active bookings
        $users = $match->bookings()
            ->with('user')
            ->whereIn('status', ['confirmed', 'pending'])
            ->get()
            ->pluck('user')
            ->unique('id')
            ->filter();

        if ($users->isEmpty()) {
            return [
                'success' => false,
                'message' => 'No active bookings found for this match.',
            ];
        }

        // Send notification to each user
        Notification::send($users, new MatchReminderNotification($match));

        $match->update(['last_reminder_sent_at' => now()]);

        return [
            'success' => true,
            'message' => "Reminder sent to {$users->count()} user(s).",
            'users_notified' => $users->count(),
        ];
    }
}
