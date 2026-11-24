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
use App\Services\Wallet\BookingPaymentService;
use App\Services\Wallet\FeeSettingsService;
use App\Services\Wallet\WalletService;
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
        $booking = DB::transaction(function () use ($user, $data) {
            // Lock the match so two customers can't hold the same seat at once.
            $match = GameMatch::with(['branch.seatingSections'])
                ->lockForUpdate()
                ->findOrFail($data['match_id']);

            // Retrying while a booking awaits payment returns that same booking.
            $existing = Booking::openPendingFor($user->id, $match->id);
            if ($existing) {
                return $existing;
            }

            // Get seats with sections
            $seats = Seat::with('section')
                ->whereIn('id', $data['seat_ids'])
                ->get();

            // Reject seats already booked for this match
            $alreadyBooked = DB::table('booking_seats')
                ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
                ->where('bookings.match_id', $match->id)
                ->whereIn('bookings.status', ['pending', 'confirmed', 'checked_in'])
                ->whereIn('booking_seats.seat_id', $data['seat_ids'])
                ->pluck('booking_seats.seat_id')
                ->toArray();

            if (!empty($alreadyBooked)) {
                throw new \Exception('One or more selected seats are already booked for this match.');
            }

            // Calculate costs
            $guests = (int) ($data['guests_count'] ?? count($data['seat_ids']));
            $fees = app(FeeSettingsService::class);
            $online = $fees->onlineEnabled();
            $costs = $fees->quote($match->branch->cafe, $this->calculateSubtotal($match, $seats), $guests, $online);

            // Generate booking code
            $bookingCode = $this->generateBookingCode();

            // Create booking
            $booking = Booking::create([
                'booking_code' => $bookingCode,
                'user_id' => $user->id,
                'match_id' => $match->id,
                'branch_id' => $match->branch_id,
                'guests_count' => $guests,
                // Pending until paid online, or until check-in in pay-at-cafe mode.
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $online ? 'online' : 'pay_at_cafe',
                'payment_expires_at' => $online
                    ? now()->addMinutes((int) $fees->block('booking')['payment_hold_minutes'])
                    : null,
                'special_requests' => $data['special_requests'] ?? null,
                'subtotal' => $costs['subtotal'],
                'service_fee' => $costs['service_fee'],
                'total_amount' => $costs['total_amount'],
                'commission_amount' => $costs['commission_amount'],
                'gateway_fee' => $costs['gateway_fee'],
                'cafe_net_amount' => $costs['cafe_net_amount'],
                'fees_snapshot' => $costs['fees_snapshot'],
                'currency' => 'SAR',
            ]);

            // Attach seats to booking
            $booking->seats()->attach($data['seat_ids']);

            // Decrement match seats_available
            $match->decrement('seats_available', count($data['seat_ids']));

            // Online bookings get their QR when paid; pay-at-cafe needs one to scan.
            if (!$online) {
                $booking->update(['qr_code' => $this->generateQrCode($booking)]);
            }

            // Create pending payment
            $this->createPayment($booking);

            return $booking;
        });

        // Post-commit side effects. Notifications run synchronously (QUEUE_CONNECTION=sync),
        // so a send failure here must NOT roll back an already-committed booking (BUG-073).
        // An online booking is announced when it is paid (BookingPaymentService).
        if ($booking->wasRecentlyCreated && $booking->payment_method === 'pay_at_cafe') {
            try {
                event(new BookingCreated($booking));
            } catch (\Throwable $e) {
                \Log::warning("BookingCreated side effects failed for booking {$booking->id}: {$e->getMessage()}");
            }
        }

        return $booking->load(['match.homeTeam', 'match.awayTeam', 'match.branch.cafe', 'seats.section', 'payment']);
    }

    /**
     * Seat prices plus section extras, always from the database.
     */
    protected function calculateSubtotal(GameMatch $match, Collection $seats): float
    {
        $subtotal = 0;

        // Calculate base price for each seat
        foreach ($seats as $seat) {
            $basePrice = $match->price_per_seat;
            $extraCost = $seat->section->extra_cost ?? 0;
            $subtotal += ($basePrice + $extraCost);
        }

        return round($subtotal, 2);
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
    public function generateQrCode(Booking $booking): string
    {
        $qrData = json_encode([
            'booking_id' => $booking->id,
            'booking_code' => $booking->booking_code,
            'user_id' => $booking->user_id,
            'match_id' => $booking->match_id,
            'generated_at' => now()->toIso8601String(),
        ]);

        // Generate QR code as SVG (no Imagick/GD required)
        $qrCode = base64_encode(QrCode::format('svg')->size(300)->generate($qrData));

        return "data:image/svg+xml;base64,{$qrCode}";
    }

    /**
     * Award loyalty points to user via loyalty card
     */
    public function awardLoyaltyPoints(User $user, Booking $booking): void
    {
        // Earned once per booking, when it is paid or checked in, so an abandoned
        // booking can't farm points.
        if (LoyaltyTransaction::where('booking_id', $booking->id)->where('type', 'earned')->where('description', 'Booking reward')->exists()) {
            return;
        }

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
            if ($booking->status === 'checked_in') {
                throw new \Exception('Cannot cancel a booking after check-in.');
            }

            // Match times are stored in Riyadh time.
            $matchDateTime = app(WalletService::class)->kickOff($booking->match);

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

            // Reverse the ledger and refund a real charge through Moyasar. A charge
            // still in 3DS keeps its status so a late success gets refunded too.
            app(BookingPaymentService::class)->handleCancellation($booking);
            if ($booking->payment && !$booking->payment->gateway_ref) {
                $booking->payment->update(['status' => 'refunded']);
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
