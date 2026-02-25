<?php

namespace App\Services;

use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Models\Booking;
use App\Models\GameMatch;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyTransaction;
use App\Models\Payment;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BookingService
{
    /**
     * Create a new booking
     */
    public function createBooking(User $user, array $data): Booking
    {
        return DB::transaction(function () use ($user, $data) {
            // Get match with relationships
            $match = GameMatch::with(['branch.seatingSections'])
                ->findOrFail($data['match_id']);

            // Get seats with sections
            $seats = Seat::with('section')
                ->whereIn('id', $data['seat_ids'])
                ->get();

            // Calculate costs
            $costs = $this->calculateBookingCosts($match, $seats);

            // Generate booking code
            $bookingCode = $this->generateBookingCode();

            // Create booking
            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'user_id' => $user->id,
                'match_id' => $match->id,
                'branch_id' => $match->branch_id,
                'guests_count' => $data['guests_count'] ?? count($data['seat_ids']),
                'status' => 'confirmed',
                'special_requests' => $data['special_requests'] ?? null,
                'subtotal' => $costs['subtotal'],
                'service_fee' => $costs['service_fee'],
                'total_amount' => $costs['total_amount'],
                'currency' => 'SAR',
            ]);

            // Attach seats to booking
            $booking->seats()->attach($data['seat_ids']);

            // Decrement match seats_available
            $match->decrement('seats_available', count($data['seat_ids']));

            // Generate QR code
            $qrCode = $this->generateQrCode($booking);
            $booking->update(['qr_code' => $qrCode]);

            // Award loyalty points
            $this->awardLoyaltyPoints($user, $booking);

            // Create pending payment
            $this->createPayment($booking);

            // Dispatch event
            event(new BookingCreated($booking));

            return $booking->load(['match.homeTeam', 'match.awayTeam', 'match.branch.cafe', 'seats.section', 'payment']);
        });
    }

    /**
     * Calculate booking costs
     */
    protected function calculateBookingCosts(GameMatch $match, Collection $seats): array
    {
        $subtotal = 0;

        // Calculate base price for each seat
        foreach ($seats as $seat) {
            $basePrice = $match->price_per_seat;
            $extraCost = $seat->section->extra_cost ?? 0;
            $subtotal += ($basePrice + $extraCost);
        }

        // Calculate service fee (5% minimum 5 SAR)
        $serviceFee = max(5, $subtotal * 0.05);

        $totalAmount = $subtotal + $serviceFee;

        return [
            'subtotal' => round($subtotal, 2),
            'service_fee' => round($serviceFee, 2),
            'total_amount' => round($totalAmount, 2),
        ];
    }

    /**
     * Generate unique booking code
     */
    protected function generateBookingCode(): string
    {
        do {
            $code = 'BOOK-' . strtoupper(\Illuminate\Support\Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }

    /**
     * Generate QR code for booking
     */
    protected function generateQrCode(Booking $booking): string
    {
        $qrData = json_encode([
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'user_id' => $booking->user_id,
            'match_id' => $booking->match_id,
            'generated_at' => now()->toIso8601String(),
        ]);

        // Generate QR code as base64 PNG
        $qrCode = base64_encode(QrCode::format('png')->size(300)->generate($qrData));

        return "data:image/png;base64,{$qrCode}";
    }

    /**
     * Award loyalty points to user via loyalty card
     */
    protected function awardLoyaltyPoints(User $user, Booking $booking): void
    {
        $points = 10;
        
        // Find or create loyalty card for user
        $loyaltyCard = $user->loyaltyCard;
        
        if (!$loyaltyCard) {
            // Create loyalty card if it doesn't exist
            $loyaltyCard = LoyaltyCard::create([
                'user_id' => $user->id,
                'card_number' => 'LC-' . str_pad($user->id, 8, '0', STR_PAD_LEFT),
                'points' => 0,
                'tier' => 'bronze',
                'total_points_earned' => 0,
                'issued_date' => now(),
            ]);
        }
        
        // Award points
        $loyaltyCard->increment('points', $points);
        $loyaltyCard->increment('total_points_earned', $points);
        
        // Create transaction record
        LoyaltyTransaction::create([
            'loyalty_card_id' => $loyaltyCard->id,
            'booking_id' => $booking->id,
            'points' => $points,
            'type' => 'earned',
            'description' => 'Booking reward',
        ]);
    }

    /**
     * Create pending payment for booking
     */
    protected function createPayment(Booking $booking): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'user_id' => $booking->user_id,
            'amount' => $booking->total_amount,
            'currency' => $booking->currency,
            'status' => 'pending',
            'type' => 'booking',
            'description' => "Booking payment for {$booking->booking_code}",
        ]);
    }

    /**
     * Get user's bookings with optional filters
     */
    public function getUserBookings(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Booking::where('user_id', $user->id)
            ->with([
                'match.homeTeam',
                'match.awayTeam',
                'match.branch.cafe',
                'seats',
                'payment'
            ])
            ->orderByDesc('created_at');

        // Apply tab filter
        if (!empty($filters['tab'])) {
            $tab = $filters['tab'];
            
            if ($tab === 'upcoming') {
                // Upcoming: match status IN (upcoming, live) AND booking status IN (confirmed, pending, checked_in)
                $query->whereIn('status', ['confirmed', 'pending', 'checked_in'])
                    ->whereHas('match', function ($q) {
                        $q->whereIn('status', ['upcoming', 'live']);
                    });
            } elseif ($tab === 'past') {
                // Past: match status = finished OR match_date in the past OR booking completed
                $query->where(function($q) {
                    $q->whereHas('match', function ($mq) {
                        $mq->where('status', 'finished')
                           ->orWhere('match_date', '<', now()->toDateString());
                    })->orWhereIn('status', ['completed']);
                });
            } elseif ($tab === 'cancelled') {
                // Cancelled: booking status = cancelled
                $query->where('status', 'cancelled');
            }
            // No tab filter means return all
        }

        // Apply legacy status filter (for backward compatibility)
        if (!empty($filters['status'])) {
            $status = $filters['status'];
            
            if ($status === 'past') {
                // Past bookings: finished or checked_in matches that occurred in the past
                $query->whereHas('match', function ($q) {
                    $q->where('match_date', '<', now());
                });
            } elseif ($status === 'cancelled') {
                $query->where('status', 'cancelled');
            } else {
                $query->where('status', $status);
            }
        }

        return $query->paginate($perPage);
    }

    /**
     * Get tab counts for user's bookings
     */
    public function getTabCounts(User $user): array
    {
        $baseQuery = Booking::where('user_id', $user->id);

        // Upcoming: match status IN (upcoming, live) AND booking status IN (confirmed, pending, checked_in)
        $upcomingCount = (clone $baseQuery)
            ->whereIn('status', ['confirmed', 'pending', 'checked_in'])
            ->whereHas('match', function ($q) {
                $q->whereIn('status', ['upcoming', 'live']);
            })
            ->count();

        // Past: match status = finished
        $pastCount = (clone $baseQuery)
            ->whereHas('match', function ($q) {
                $q->where('status', 'finished');
            })
            ->count();

        // Cancelled: booking status = cancelled
        $cancelledCount = (clone $baseQuery)
            ->where('status', 'cancelled')
            ->count();

        return [
            'upcoming' => $upcomingCount,
            'past' => $pastCount,
            'cancelled' => $cancelledCount,
        ];
    }

    /**
     * Get booking by ID for authenticated user
     */
    public function getBookingById(int $bookingId, User $user): ?Booking
    {
        return Booking::with([
            'match.homeTeam',
            'match.awayTeam',
            'match.branch.cafe',
            'seats.section',
            'players',
            'payment',
        ])
            ->where('id', $bookingId)
            ->where('user_id', $user->id)
            ->first();
    }

    /**
     * Update booking
     */
    public function updateBooking(Booking $booking, array $data): Booking
    {
        $booking->update($data);
        return $booking->fresh(['match', 'seats', 'payment']);
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            // Check if match has not started (compare dates only)
            $matchDateTime = \Carbon\Carbon::parse(
                $booking->match->match_date->format('Y-m-d') . ' ' . $booking->match->kick_off->format('H:i:s')
            );
            
            if ($matchDateTime->lte(now())) {
                throw new \Exception('Cannot cancel booking for matches that have started or finished.');
            }

            // Update booking status
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Release seats - increment match seats_available
            $seatsCount = $booking->seats()->count();
            $booking->match->increment('seats_available', $seatsCount);

            // Release individual seats
            $booking->seats()->update(['is_available' => true]);

            // Update payment status if exists
            if ($booking->payment) {
                $booking->payment->update(['status' => 'refunded']);
            }

            // Deduct loyalty points if they were awarded
            $loyaltyCard = $booking->user->loyaltyCard;
            if ($loyaltyCard && $loyaltyCard->points >= 10) {
                $loyaltyCard->decrement('points', 10);
                
                // Create transaction record for deduction
                LoyaltyTransaction::create([
                    'loyalty_card_id' => $loyaltyCard->id,
                    'booking_id' => $booking->id,
                    'points' => -10,
                    'type' => 'redeemed',
                    'description' => 'Booking cancellation penalty',
                ]);
            }

            // Dispatch event
            event(new BookingCancelled($booking));

            return $booking->fresh(['match', 'seats', 'payment']);
        });
    }

    /**
     * Get entry pass data
     */
    public function getEntryPass(Booking $booking): array
    {
        return [
            'booking_code' => $booking->booking_code,
            'qr_code' => $booking->qr_code,
            'match' => [
                'home_team' => $booking->match->homeTeam->name,
                'away_team' => $booking->match->awayTeam->name,
                'league' => $booking->match->league,
                'match_date' => $booking->match->match_date->format('Y-m-d'),
                'kick_off' => $booking->match->kick_off,
            ],
            'venue' => [
                'branch' => $booking->branch->name,
                'cafe' => $booking->branch->cafe->name,
                'address' => $booking->branch->address,
            ],
            'seats' => $booking->seats->map(function ($seat) {
                return [
                    'section' => $seat->section->name,
                    'label' => $seat->label,
                ];
            }),
            'guests_count' => $booking->guests_count,
            'status' => $booking->status,
        ];
    }

    /**
     * Get shareable booking data
     */
    public function getShareableData(Booking $booking): array
    {
        return [
            'booking_code' => $booking->booking_code,
            'match' => [
                'home_team' => $booking->match->homeTeam->name,
                'home_team_logo' => $booking->match->homeTeam->logo,
                'away_team' => $booking->match->awayTeam->name,
                'away_team_logo' => $booking->match->awayTeam->logo,
                'league' => $booking->match->league,
                'match_date' => $booking->match->match_date->format('l, F j, Y'),
                'kick_off' => $booking->match->kick_off,
            ],
            'venue' => [
                'name' => $booking->branch->cafe->name . ' - ' . $booking->branch->name,
                'address' => $booking->branch->address,
            ],
            'seats_count' => $booking->seats->count(),
            'guests_count' => $booking->guests_count,
            'share_text' => "I'm watching {$booking->match->homeTeam->name} vs {$booking->match->awayTeam->name} at {$booking->branch->cafe->name}! 🎉",
        ];
    }

    /**
     * Get ICS calendar data for add-to-calendar
     */
    public function getCalendarData(Booking $booking): string
    {
        $match = $booking->match;
        
        // Parse match date and kick_off time
        $matchDateTime = \Carbon\Carbon::parse($match->match_date->format('Y-m-d') . ' ' . $match->kick_off);
        $endDateTime = $matchDateTime->copy()->addHours(2); // Assume 2 hour duration

        $summary = "{$match->homeTeam->name} vs {$match->awayTeam->name}";
        $description = "Match: {$match->league}\\nVenue: {$booking->branch->cafe->name} - {$booking->branch->name}\\nBooking Code: {$booking->booking_code}\\nSeats: {$booking->seats->pluck('label')->join(', ')}";
        $location = "{$booking->branch->cafe->name} - {$booking->branch->name}, {$booking->branch->address}";

        $ics = "BEGIN:VCALENDAR\r\n";
        $ics .= "VERSION:2.0\r\n";
        $ics .= "PRODID:-//MatchDay//Booking Calendar//EN\r\n";
        $ics .= "BEGIN:VEVENT\r\n";
        $ics .= "UID:" . uniqid() . "@matchday.com\r\n";
        $ics .= "DTSTAMP:" . gmdate('Ymd\THis\Z') . "\r\n";
        $ics .= "DTSTART:" . $matchDateTime->format('Ymd\THis\Z') . "\r\n";
        $ics .= "DTEND:" . $endDateTime->format('Ymd\THis\Z') . "\r\n";
        $ics .= "SUMMARY:" . $summary . "\r\n";
        $ics .= "DESCRIPTION:" . $description . "\r\n";
        $ics .= "LOCATION:" . $location . "\r\n";
        $ics .= "STATUS:CONFIRMED\r\n";
        $ics .= "END:VEVENT\r\n";
        $ics .= "END:VCALENDAR\r\n";

        return $ics;
    }

    /**
     * Add player to booking
     */
    public function addPlayer(Booking $booking, array $data): \App\Models\BookingPlayer
    {
        return $booking->players()->create([
            'user_id' => $booking->user_id,
            'name' => $data['name'],
            'position' => $data['position'] ?? null,
            'jersey_number' => $data['jersey_number'] ?? null,
            'is_captain' => false,
        ]);
    }

    /**
     * Delete player from booking
     */
    public function deletePlayer(Booking $booking, int $playerId): bool
    {
        $player = $booking->players()->where('id', $playerId)->first();

        if (!$player) {
            return false;
        }

        return $player->delete();
    }

    /**
     * Get booking players
     */
    public function getBookingPlayers(Booking $booking): Collection
    {
        return $booking->players;
    }
}
