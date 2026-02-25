<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cafe;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingAdminService
{
    /**
     * List bookings for a café's branches with filters.
     */
    public function listBookings(Cafe $cafe, array $filters = []): array
    {
        $branchIds = $cafe->branches()->pluck('id');

        $query = Booking::where(function($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds)
                  ->orWhereHas('match', function($q2) use ($branchIds) {
                      $q2->whereIn('branch_id', $branchIds);
                  });
            })
            ->with([
                'user:id,name,phone,avatar',
                'user.loyaltyCard:id,user_id,tier',
                'match:id,home_team_id,away_team_id,league,match_date,kick_off,status',
                'match.homeTeam:id,name,short_name,logo',
                'match.awayTeam:id,name,short_name,logo',
                'seats:id,label,section_id',
                'seats.section:id,name',
                'payment:id,booking_id,amount,status,payment_method_id',
                'payment.paymentMethod:id,type,card_last_four',
            ]);

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by match
        if (!empty($filters['match_id'])) {
            $query->where('match_id', $filters['match_id']);
        }

        // Filter by date (match date)
        if (!empty($filters['date'])) {
            $query->whereHas('match', function ($q) use ($filters) {
                $q->whereDate('match_date', $filters['date']);
            });
        }

        $bookings = $query->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);

        // Precompute is_returning for all users in this batch
        $userIds = $bookings->pluck('user_id')->unique()->values()->toArray();
        $returningUsers = $this->getReturningUserIds($branchIds, $userIds);

        return [
            'bookings' => $bookings,
            'returning_user_ids' => $returningUsers,
        ];
    }

    /**
     * Get a single booking with full admin detail.
     */
    public function getBookingDetail(Booking $booking, Cafe $cafe): array
    {
        $booking->load([
            'user:id,name,phone,email,avatar',
            'user.loyaltyCard:id,user_id,tier,points',
            'match:id,branch_id,home_team_id,away_team_id,league,match_date,kick_off,status,price_per_seat',
            'match.homeTeam:id,name,short_name,logo',
            'match.awayTeam:id,name,short_name,logo',
            'match.branch:id,name',
            'seats:id,label,section_id,table_number',
            'seats.section:id,name',
            'payment:id,booking_id,amount,status,paid_at,payment_method_id',
            'payment.paymentMethod:id,type,card_last_four,card_holder',
        ]);

        // is_returning: 2+ bookings at this cafe's branches
        $branchIds = $cafe->branches()->pluck('id');
        $bookingCountAtCafe = Booking::where('user_id', $booking->user_id)
            ->whereIn('branch_id', $branchIds)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->count();

        return [
            'booking' => $booking,
            'is_returning' => $bookingCountAtCafe >= 2,
        ];
    }

    /**
     * Check-in a booking.
     */
    public function checkIn(Booking $booking): array
    {
        // Must be confirmed
        if ($booking->status !== 'confirmed') {
            return [
                'success' => false,
                'status' => 422,
                'message' => "Cannot check in a booking with status '{$booking->status}'. Only confirmed bookings can be checked in.",
            ];
        }

        $booking->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        $booking->load([
            'user:id,name,phone',
            'seats:id,label,section_id',
            'seats.section:id,name',
        ]);

        // Bust related caches
        Cache::forget("match_admin_detail_{$booking->match_id}");

        return [
            'success' => true,
            'booking' => $booking,
        ];
    }

    /**
     * Admin cancel a booking: refund + release seats.
     */
    public function cancelBooking(Booking $booking): array
    {
        if (in_array($booking->status, ['cancelled', 'checked_in'])) {
            return [
                'success' => false,
                'status' => 409,
                'message' => "Cannot cancel a booking with status '{$booking->status}'.",
            ];
        }

        return DB::transaction(function () use ($booking) {
            // Release seats
            $releasedSeats = $booking->seats()->count();
            $booking->seats()->detach();

            // Refund payment
            $refunded = false;
            if ($booking->payment && $booking->payment->status === 'paid') {
                $booking->payment->update([
                    'status' => 'refunded',
                ]);
                $refunded = true;
            }

            // Cancel booking
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Notify user
            try {
                $booking->load('match.homeTeam', 'match.awayTeam');
                $booking->user->notify(new BookingCancelledNotification($booking));
            } catch (\Exception $e) {
                // Don't fail on notification error
            }

            // Bust cache
            Cache::forget("match_admin_detail_{$booking->match_id}");

            return [
                'success' => true,
                'seats_released' => $releasedSeats,
                'refunded' => $refunded,
            ];
        });
    }

    /**
     * Today's booking summary for the café.
     */
    public function getTodaySummary(Cafe $cafe): array
    {
        $branchIds = $cafe->branches()->pluck('id');

        $todayBookings = Booking::where(function($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds)
                  ->orWhereHas('match', function($q2) use ($branchIds) {
                      $q2->whereIn('branch_id', $branchIds);
                  });
            })
            ->whereHas('match', function ($q) {
                $q->whereDate('match_date', now()->toDateString());
            })
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as checked_in,
                COALESCE(SUM(total_amount), 0) as revenue
            ")
            ->first();

        return [
            'total_bookings' => (int) ($todayBookings->total ?? 0),
            'pending' => (int) ($todayBookings->pending ?? 0),
            'checked_in' => (int) ($todayBookings->checked_in ?? 0),
            'revenue' => (float) ($todayBookings->revenue ?? 0),
        ];
    }

    /**
     * Get user IDs who have 2+ confirmed/checked_in bookings at this café.
     */
    private function getReturningUserIds($branchIds, array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return Booking::whereIn('branch_id', $branchIds)
            ->whereIn('user_id', $userIds)
            ->whereIn('status', ['confirmed', 'checked_in'])
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) >= 2')
            ->pluck('user_id')
            ->toArray();
    }
}
