<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\GameMatch;
use App\Models\Booking;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * 1. GET /api/v1/cafe-admin/dashboard
     * Dashboard overview
     * Permission: view-bookings
     */
    public function index(Request $request): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('view-bookings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view dashboard.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $cacheKey = "dashboard_stats_{$cafe->id}";

        $stats = Cache::remember($cacheKey, 900, function () use ($cafe) {
            $branchIds = $cafe->branches()->pluck('id');
            $today = now()->toDateString();

            // Today's matches
            $todayMatches = GameMatch::whereIn('branch_id', $branchIds)
                ->whereDate('match_date', $today)
                ->count();

            // Bookings today
            $bookingsToday = Booking::whereIn('branch_id', $branchIds)
                ->whereDate('created_at', $today)
                ->count();

            // Current occupancy percentage (guests checked in vs total seats)
            $checkedInGuests = Booking::whereIn('branch_id', $branchIds)
                ->whereDate('created_at', $today)
                ->where('status', 'checked_in')
                ->sum('guests_count');

            $totalSeats = $cafe->branches()->sum('total_seats');
            $occupancyPct = $totalSeats > 0 ? round(($checkedInGuests / $totalSeats) * 100, 1) : 0;

            // Quick actions available (based on permissions)
            $quickActionsAvailable = [];
            if ($cafe->owner->can('manage-matches')) {
                $quickActionsAvailable[] = 'add_match';
            }
            if ($cafe->owner->can('view-bookings')) {
                $quickActionsAvailable[] = 'view_bookings';
            }
            if ($cafe->owner->can('manage-staff')) {
                $quickActionsAvailable[] = 'invite_staff';
            }
            if ($cafe->owner->can('manage-offers')) {
                $quickActionsAvailable[] = 'create_offer';
            }

            return [
                'today_matches' => $todayMatches,
                'bookings_today' => $bookingsToday,
                'occupancy_pct' => $occupancyPct,
                'quick_actions_available' => $quickActionsAvailable,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * 2. GET /api/v1/cafe-admin/dashboard/upcoming-matches
     * Next 5 upcoming matches with live badge and booking counts
     * Permission: view-bookings
     */
    public function upcomingMatches(Request $request): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('view-bookings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view dashboard.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $cacheKey = "dashboard_upcoming_matches_{$cafe->id}";

        $matches = Cache::remember($cacheKey, 900, function () use ($cafe) {
            $branchIds = $cafe->branches()->pluck('id');

            return GameMatch::whereIn('branch_id', $branchIds)
                ->where('match_date', '>=', now()->toDateString())
                ->whereIn('status', ['upcoming', 'live'])
                ->with(['homeTeam:id,name,logo', 'awayTeam:id,name,logo', 'branch:id,name,total_seats'])
                ->withCount(['bookings as confirmed_bookings' => function ($q) {
                    $q->whereIn('status', ['confirmed', 'checked_in']);
                }])
                ->orderBy('match_date')
                ->orderBy('kick_off')
                ->limit(5)
                ->get()
                ->map(function ($match) {
                    $seatsTotal = $match->branch->total_seats ?? 0;
                    $seatsBooked = $match->confirmed_bookings ?? 0;
                    $seatsAvailable = max(0, $seatsTotal - $seatsBooked);

                    return [
                        'match_id' => $match->id,
                        'home_team' => [
                            'name' => $match->homeTeam->name,
                            'logo' => $match->homeTeam->logo,
                        ],
                        'away_team' => [
                            'name' => $match->awayTeam->name,
                            'logo' => $match->awayTeam->logo,
                        ],
                        'match_date' => $match->match_date,
                        'kick_off' => $match->kick_off,
                        'status' => $match->status,
                        'is_live' => $match->status === 'live',
                        'branch_name' => $match->branch->name,
                        'bookings_count' => $seatsBooked,
                        'available_seats' => $seatsAvailable,
                        'total_seats' => $seatsTotal,
                        'occupancy_pct' => $seatsTotal > 0 ? round(($seatsBooked / $seatsTotal) * 100, 1) : 0,
                    ];
                })
                ->toArray();
        });

        return response()->json([
            'success' => true,
            'data' => $matches,
        ]);
    }

    /**
     * 3. GET /api/v1/cafe-admin/dashboard/recent-bookings
     * Last 10 bookings with customer details
     * Permission: view-bookings
     */
    public function recentBookings(Request $request): JsonResponse
    {
        // Check permission
        if (!$request->user()->can('view-bookings')) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view dashboard.',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner.',
            ], 404);
        }

        $cacheKey = "dashboard_recent_bookings_{$cafe->id}";

        $bookings = Cache::remember($cacheKey, 900, function () use ($cafe) {
            $branchIds = $cafe->branches()->pluck('id');

            return Booking::whereIn('branch_id', $branchIds)
                ->with(['user:id,name,avatar', 'match:id,match_date,kick_off', 'payment:id,booking_id,amount'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($booking) {
                    // Format avatar
                    $avatar = null;
                    if ($booking->user->avatar && is_array($booking->user->avatar)) {
                        $avatar = [
                            'original' => url('storage/' . $booking->user->avatar['original']),
                            'medium' => url('storage/' . $booking->user->avatar['medium']),
                            'thumbnail' => url('storage/' . $booking->user->avatar['thumbnail']),
                        ];
                    }

                    return [
                        'booking_id' => $booking->id,
                        'customer_name' => $booking->user->name,
                        'customer_avatar' => $avatar,
                        'status' => $booking->status,
                        'guests_count' => $booking->guests_count,
                        'amount' => $booking->payment ? (float) $booking->payment->amount : 0,
                        'created_at' => $booking->created_at->toIso8601String(),
                        'match_date' => $booking->match && $booking->match->match_date ? Carbon::parse($booking->match->match_date)->format('M j, Y') : null,
                        'status_badge' => match ($booking->status) {
                            'pending' => 'warning',
                            'confirmed' => 'success',
                            'checked_in' => 'info',
                            'completed' => 'success',
                            'cancelled' => 'danger',
                            default => 'secondary',
                        },
                    ];
                })
                ->toArray();
        });

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }
}
