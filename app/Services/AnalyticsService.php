<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Cafe;
use App\Models\GameMatch;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    protected const CACHE_TTL = 900; // 15 minutes

    /**
     * Get period dates based on filter
     */
    public function getPeriodDates(?string $period = null, ?string $from = null, ?string $to = null, int $weekOffset = 0): array
    {
        $now = now();

        // Custom date range
        if ($from && $to) {
            return [
                'start' => Carbon::parse($from)->startOfDay(),
                'end' => Carbon::parse($to)->endOfDay(),
                'label' => Carbon::parse($from)->format('M j') . ' - ' . Carbon::parse($to)->format('M j'),
            ];
        }

        // Week offset
        if ($weekOffset !== 0) {
            $startOfWeek = $now->copy()->addWeeks($weekOffset)->startOfWeek();
            $endOfWeek = $now->copy()->addWeeks($weekOffset)->endOfWeek();
            return [
                'start' => $startOfWeek,
                'end' => $endOfWeek,
                'label' => $startOfWeek->format('M j') . ' - ' . $endOfWeek->format('M j'),
            ];
        }

        // Predefined periods
        return match ($period) {
            'this_week' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'label' => $now->copy()->startOfWeek()->format('M j') . ' - ' . $now->copy()->endOfWeek()->format('M j'),
            ],
            'this_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => $now->format('F Y'),
            ],
            'last_month' => [
                'start' => $now->copy()->subMonth()->startOfMonth(),
                'end' => $now->copy()->subMonth()->endOfMonth(),
                'label' => $now->copy()->subMonth()->format('F Y'),
            ],
            'last_3_months' => [
                'start' => $now->copy()->subMonths(3)->startOfMonth(),
                'end' => $now->copy()->endOfMonth(),
                'label' => 'Last 3 Months',
            ],
            default => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek(),
                'label' => $now->copy()->startOfWeek()->format('M j') . ' - ' . $now->copy()->endOfWeek()->format('M j'),
            ],
        };
    }

    /**
     * Get previous period dates for comparison
     */
    protected function getPreviousPeriodDates(Carbon $start, Carbon $end): array
    {
        $duration = $start->diffInDays($end) + 1;
        return [
            'start' => $start->copy()->subDays($duration),
            'end' => $end->copy()->subDays($duration),
        ];
    }

    /**
     * Calculate change percentage
     */
    protected function calculateChange(float $current, float $previous): array
    {
        if ($previous == 0) {
            return [
                'change' => $current > 0 ? '+100%' : '0%',
                'trend' => $current > 0 ? 'up' : 'neutral',
            ];
        }

        $changePercent = (($current - $previous) / $previous) * 100;
        $sign = $changePercent > 0 ? '+' : '';

        return [
            'change' => $sign . number_format($changePercent, 1) . '%',
            'trend' => $changePercent > 0 ? 'up' : ($changePercent < 0 ? 'down' : 'neutral'),
        ];
    }

    /**
     * Get analytics overview with period comparison
     */
    public function getOverview(Cafe $cafe, array $periodDates): array
    {
        $cacheKey = "analytics_overview_{$cafe->id}_{$periodDates['start']->format('Y-m-d')}_{$periodDates['end']->format('Y-m-d')}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cafe, $periodDates) {
            // Current period stats
            $currentStats = $this->getPeriodStats($cafe, $periodDates['start'], $periodDates['end']);

            // Previous period stats for comparison
            $previousPeriod = $this->getPreviousPeriodDates($periodDates['start'], $periodDates['end']);
            $previousStats = $this->getPeriodStats($cafe, $previousPeriod['start'], $previousPeriod['end']);

            // Calculate changes
            $bookingsChange = $this->calculateChange($currentStats['bookings'], $previousStats['bookings']);
            $revenueChange = $this->calculateChange($currentStats['revenue'], $previousStats['revenue']);
            $customersChange = $this->calculateChange($currentStats['new_customers'], $previousStats['new_customers']);
            $ratingChange = [
                'change' => $currentStats['avg_rating'] - $previousStats['avg_rating'] > 0 
                    ? '+' . number_format($currentStats['avg_rating'] - $previousStats['avg_rating'], 1)
                    : number_format($currentStats['avg_rating'] - $previousStats['avg_rating'], 1),
                'trend' => $currentStats['avg_rating'] > $previousStats['avg_rating'] ? 'up' : 
                          ($currentStats['avg_rating'] < $previousStats['avg_rating'] ? 'down' : 'neutral'),
            ];

            return [
                'period_label' => $periodDates['label'],
                'total_bookings' => [
                    'value' => $currentStats['bookings'],
                    'change' => $bookingsChange['change'],
                    'trend' => $bookingsChange['trend'],
                ],
                'revenue' => [
                    'value' => $currentStats['revenue'],
                    'change' => $revenueChange['change'],
                    'trend' => $revenueChange['trend'],
                ],
                'new_customers' => [
                    'value' => $currentStats['new_customers'],
                    'change' => $customersChange['change'],
                    'trend' => $customersChange['trend'],
                ],
                'avg_rating' => [
                    'value' => $currentStats['avg_rating'],
                    'change' => $ratingChange['change'],
                    'trend' => $ratingChange['trend'],
                ],
            ];
        });
    }

    /**
     * Get stats for a specific period
     */
    public function getPeriodStats(Cafe $cafe, Carbon $start, Carbon $end): array
    {
        $branchIds = $cafe->branches()->pluck('id');

        // Total bookings
        $bookings = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        // Revenue
        $revenue = Payment::whereHas('booking', function ($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds);
            })
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->sum('amount');

        // New customers (users who made their first booking in this period)
        $newCustomers = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$start, $end])
            ->select('user_id')
            ->distinct()
            ->get()
            ->filter(function ($booking) use ($start) {
                return Booking::where('user_id', $booking->user_id)
                    ->where('created_at', '<', $start)
                    ->doesntExist();
            })
            ->count();

        // Average rating
        $avgRating = DB::table('reviews')
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$start, $end])
            ->avg('rating') ?? 0;

        return [
            'bookings' => $bookings,
            'revenue' => (float) $revenue,
            'new_customers' => $newCustomers,
            'avg_rating' => round($avgRating, 1),
        ];
    }

    /**
     * Get bookings chart data
     */
    public function getBookingsChart(Cafe $cafe, array $periodDates): array
    {
        $cacheKey = "analytics_bookings_chart_{$cafe->id}_{$periodDates['start']->format('Y-m-d')}_{$periodDates['end']->format('Y-m-d')}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cafe, $periodDates) {
            $branchIds = $cafe->branches()->pluck('id');

            $data = Booking::whereIn('branch_id', $branchIds)
                ->whereBetween('created_at', [$periodDates['start'], $periodDates['end']])
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => [
                    'date' => Carbon::parse($item->date)->format('Y-m-d'),
                    'count' => (int) $item->count,
                ])
                ->toArray();

            return $data;
        });
    }

    /**
     * Get revenue chart data
     */
    public function getRevenueChart(Cafe $cafe, array $periodDates): array
    {
        $cacheKey = "analytics_revenue_chart_{$cafe->id}_{$periodDates['start']->format('Y-m-d')}_{$periodDates['end']->format('Y-m-d')}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cafe, $periodDates) {
            $branchIds = $cafe->branches()->pluck('id');

            $data = Payment::whereHas('booking', function ($q) use ($branchIds) {
                    $q->whereIn('branch_id', $branchIds);
                })
                ->whereBetween('created_at', [$periodDates['start'], $periodDates['end']])
                ->where('status', 'completed')
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount) as amount'))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(fn($item) => [
                    'date' => Carbon::parse($item->date)->format('Y-m-d'),
                    'amount' => (float) $item->amount,
                ])
                ->toArray();

            return $data;
        });
    }

    /**
     * Get peak hours data
     */
    public function getPeakHours(Cafe $cafe): array
    {
        $cacheKey = "analytics_peak_hours_{$cafe->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cafe) {
            $branchIds = $cafe->branches()->pluck('id');

            $data = Booking::whereIn('branch_id', $branchIds)
                ->whereIn('status', ['confirmed', 'checked_in', 'completed'])
                ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as bookings_count'))
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->map(fn($item) => [
                    'hour' => (int) $item->hour,
                    'bookings_count' => (int) $item->bookings_count,
                ])
                ->toArray();

            return $data;
        });
    }

    /**
     * Get customer analytics (new vs returning)
     */
    public function getCustomerAnalytics(Cafe $cafe, array $periodDates): array
    {
        $cacheKey = "analytics_customers_{$cafe->id}_{$periodDates['start']->format('Y-m-d')}_{$periodDates['end']->format('Y-m-d')}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cafe, $periodDates) {
            $branchIds = $cafe->branches()->pluck('id');

            $allCustomers = Booking::whereIn('branch_id', $branchIds)
                ->whereBetween('created_at', [$periodDates['start'], $periodDates['end']])
                ->distinct('user_id')
                ->pluck('user_id');

            $newCustomers = $allCustomers->filter(function ($userId) use ($periodDates) {
                return Booking::where('user_id', $userId)
                    ->where('created_at', '<', $periodDates['start'])
                    ->doesntExist();
            });

            $newCount = $newCustomers->count();
            $returningCount = $allCustomers->count() - $newCount;
            $total = $allCustomers->count();

            return [
                'new_count' => $newCount,
                'returning_count' => $returningCount,
                'new_vs_returning_ratio' => $total > 0 ? round(($newCount / $total) * 100, 1) : 0,
            ];
        });
    }

    /**
     * Get best performing matches
     */
    public function getBestPerformingMatches(Cafe $cafe): array
    {
        $cacheKey = "analytics_best_matches_{$cafe->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($cafe) {
            $branchIds = $cafe->branches()->pluck('id');

            $matches = GameMatch::whereIn('branch_id', $branchIds)
                ->with(['homeTeam', 'awayTeam'])
                ->withCount(['bookings as total_bookings'])
                ->withSum(['bookings' => function ($q) {
                    $q->whereHas('payment', fn($p) => $p->where('status', 'completed'));
                }], 'total_amount')
                ->having('total_bookings', '>', 0)
                ->orderByDesc('bookings_sum_total_amount')
                ->limit(10)
                ->get()
                ->map(fn($match) => [
                    'match_id' => $match->id,
                    'home_team' => $match->homeTeam->name ?? 'TBD',
                    'away_team' => $match->awayTeam->name ?? 'TBD',
                    'date' => $match->match_date ?? null,
                    'bookings' => $match->total_bookings,
                    'revenue' => (float) ($match->bookings_sum_total_amount ?? 0),
                ])
                ->toArray();

            return $matches;
        });
    }

    /**
     * Clear analytics cache for a cafe
     */
    public function clearCache(int $cafeId): void
    {
        $keys = [
            "analytics_overview_{$cafeId}_*",
            "analytics_bookings_chart_{$cafeId}_*",
            "analytics_revenue_chart_{$cafeId}_*",
            "analytics_peak_hours_{$cafeId}",
            "analytics_customers_{$cafeId}_*",
            "analytics_best_matches_{$cafeId}",
            "dashboard_stats_{$cafeId}",
            "dashboard_upcoming_matches_{$cafeId}",
            "dashboard_recent_bookings_{$cafeId}",
        ];

        foreach ($keys as $pattern) {
            if (str_contains($pattern, '*')) {
                // For wildcard patterns, you'd need Redis or manual tracking
                // For now, we'll just forget exact keys
                continue;
            }
            Cache::forget($pattern);
        }
    }
}
