<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\GameMatch;
use App\Models\Payment;
use App\Models\Branch;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AnalyticsController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Dashboard overview (branch-based)
     */
    public function overview(Request $request, $branchId = null): JsonResponse
    {
        if (!$request->user()->can('view-analytics')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to view analytics.'], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe && $request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'No cafe found for this owner.'], 404);
        }

        $matchIds = $branchId
            ? GameMatch::where('branch_id', $branchId)->pluck('id')
            : ($cafe ? GameMatch::whereIn('branch_id', $cafe->branches()->pluck('id'))->pluck('id') : collect());

        $totalBookings = Booking::whereIn('match_id', $matchIds)->count();
        $totalRevenue = Payment::whereIn('booking_id', Booking::whereIn('match_id', $matchIds)->pluck('id'))
            ->where('status', 'completed')
            ->sum('amount');
        $activeMatches = $branchId
            ? GameMatch::where('branch_id', $branchId)->where('status', 'live')->count()
            : 0;

        $recentBookings = Booking::whereIn('match_id', $matchIds)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($b) {
                return ['id' => $b->id, 'status' => $b->status, 'created_at' => $b->created_at?->toDateTimeString()];
            });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard overview retrieved.',
            'data' => [
                'total_bookings' => $totalBookings,
                'total_revenue' => (float) $totalRevenue,
                'active_matches' => $activeMatches,
                'average_occupancy' => 0,
                'recent_bookings' => $recentBookings,
            ],
        ]);
    }

    /**
     * Revenue statistics (branch-based)
     */
    public function revenue(Request $request, $branchId = null): JsonResponse
    {
        if (!$request->user()->can('view-analytics')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to view analytics.'], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe && $request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'No cafe found.'], 404);
        }

        $period = $request->query('period', 'month');
        $matchIds = $branchId
            ? GameMatch::where('branch_id', $branchId)->pluck('id')
            : ($cafe ? GameMatch::whereIn('branch_id', $cafe->branches()->pluck('id'))->pluck('id') : collect());

        $bookingIds = Booking::whereIn('match_id', $matchIds)->pluck('id');
        $totalRevenue = Payment::whereIn('booking_id', $bookingIds)->where('status', 'completed')->sum('amount');

        $thisMonth = Payment::whereIn('booking_id', $bookingIds)
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $lastMonth = Payment::whereIn('booking_id', $bookingIds)
            ->where('status', 'completed')
            ->whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])
            ->sum('amount');

        $growth = $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100, 2) : 0;

        return response()->json([
            'success' => true,
            'message' => 'Revenue statistics retrieved.',
            'data' => [
                'total_revenue' => (float) $totalRevenue,
                'this_month' => (float) $thisMonth,
                'last_month' => (float) $lastMonth,
                'growth_percentage' => $growth,
                'period' => $period,
                'revenue' => (float) $totalRevenue,
                'bookings_count' => Booking::whereIn('match_id', $matchIds)->count(),
            ],
        ]);
    }

    /**
     * Booking trends (branch-based)
     */
    public function bookings(Request $request, $branchId = null): JsonResponse
    {
        if (!$request->user()->can('view-analytics')) {
            return response()->json(['success' => false, 'message' => 'You do not have permission to view analytics.'], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe && $request->user()->role !== 'admin') {
            return response()->json(['success' => false, 'message' => 'No cafe found.'], 404);
        }

        $matchIds = $branchId
            ? GameMatch::where('branch_id', $branchId)->pluck('id')
            : ($cafe ? GameMatch::whereIn('branch_id', $cafe->branches()->pluck('id'))->pluck('id') : collect());

        $totalBookings = Booking::whereIn('match_id', $matchIds)->count();

        $trendData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $count = Booking::whereIn('match_id', $matchIds)
                ->whereDate('created_at', $date->toDateString())
                ->count();
            $trendData[] = ['date' => $date->toDateString(), 'count' => $count];
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking trends retrieved.',
            'data' => [
                'total_bookings' => $totalBookings,
                'trend_data' => $trendData,
            ],
        ]);
    }

    /**
     * Peak hours analytics
     */
    public function peakHours(Request $request): JsonResponse
    {
        if (!$request->user()->can('view-analytics')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }
        return response()->json(['success' => true, 'message' => 'Peak hours retrieved.', 'data' => ['peak_hours' => []]]);
    }

    /**
     * Customer analytics
     */
    public function customers(Request $request): JsonResponse
    {
        if (!$request->user()->can('view-analytics')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }
        return response()->json(['success' => true, 'message' => 'Customer analytics retrieved.', 'data' => ['period_label' => '', 'customers' => []]]);
    }

    /**
     * Match analytics
     */
    public function matches(Request $request): JsonResponse
    {
        if (!$request->user()->can('view-analytics')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }
        return response()->json(['success' => true, 'message' => 'Match analytics retrieved.', 'data' => ['matches' => []]]);
    }

    /**
     * Subscription analytics
     */
    public function subscription(Request $request): JsonResponse
    {
        if (!$request->user()->can('manage-subscription')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }
        return response()->json(['success' => true, 'message' => 'Subscription analytics retrieved.', 'data' => ['monthly_data' => [], 'summary' => []]]);
    }

    /**
     * Chart data for revenue (branch-based)
     */
    public function chartData(Request $request, $branchId = null): JsonResponse
    {
        if (!$request->user()->can('view-analytics')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $period = $request->query('period', 'week');
        $labels = [];
        $data = [];

        if ($period === 'week') {
            for ($i = 6; $i >= 0; $i--) {
                $labels[] = now()->subDays($i)->format('D');
                $data[] = 0;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Chart data retrieved.',
            'data' => [
                'labels' => $labels,
                'datasets' => [['label' => 'Revenue', 'data' => $data]],
            ],
        ]);
    }

    /**
     * Top performing matches (branch-based)
     */
    public function topMatches(Request $request, $branchId = null): JsonResponse
    {
        if (!$request->user()->can('view-analytics')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $query = GameMatch::with(['homeTeam', 'awayTeam'])->withCount('bookings');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        } else {
            $cafe = $request->user()->ownedCafes()->first();
            if ($cafe) {
                $branchIds = $cafe->branches()->pluck('id');
                $query->whereIn('branch_id', $branchIds);
            }
        }

        $matches = $query->orderByDesc('bookings_count')->limit(10)->get();

        return response()->json([
            'success' => true,
            'message' => 'Top matches retrieved.',
            'data' => $matches->map(function ($m) {
                return [
                    'match' => [
                        'id' => $m->id,
                        'home_team' => $m->homeTeam->name ?? null,
                        'away_team' => $m->awayTeam->name ?? null,
                    ],
                    'bookings_count' => $m->bookings_count,
                    'revenue' => 0,
                ];
            }),
        ]);
    }

    /**
     * Occupancy analytics (branch-based)
     */
    public function occupancy(Request $request, $branchId = null): JsonResponse
    {
        if (!$request->user()->can('view-analytics')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        $occupancyByDay = [];
        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        foreach ($days as $day) {
            $occupancyByDay[] = ['day' => $day, 'occupancy_rate' => 0];
        }

        return response()->json([
            'success' => true,
            'message' => 'Occupancy data retrieved.',
            'data' => [
                'average_occupancy' => 0,
                'peak_occupancy' => 0,
                'occupancy_by_day' => $occupancyByDay,
            ],
        ]);
    }

    /**
     * Export analytics report (branch-based)
     */
    public function exportReport(Request $request, $branchId = null): JsonResponse
    {
        if (!$request->user()->can('view-analytics')) {
            return response()->json(['success' => false, 'message' => 'Permission denied.'], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Report exported.',
            'data' => [
                'download_url' => url('/api/v1/exports/report.pdf'),
            ],
        ]);
    }
}
