<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Booking;
use App\Models\GameMatch;
use App\Models\SeatingSection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class OccupancyService
{
    /**
     * Get full occupancy dashboard data for a branch.
     */
    public function getOccupancyDashboard(Branch $branch): array
    {
        $cacheKey = "occupancy_dashboard_{$branch->id}";

        return Cache::remember($cacheKey, 120, function () use ($branch) {
            $capacity = $branch->total_seats;

            // Get today's matches for this branch
            $todayMatches = GameMatch::where('branch_id', $branch->id)
                ->whereDate('match_date', now()->toDateString())
                ->whereIn('status', ['upcoming', 'live'])
                ->pluck('id');

            // Current guests: checked_in bookings for today's matches
            $checkedInBookings = Booking::whereIn('match_id', $todayMatches)
                ->where('status', 'checked_in')
                ->get();

            $currentGuests = $checkedInBookings->sum('guests_count');

            // Reserved: confirmed (not yet checked in) bookings for today
            $reservedBookings = Booking::whereIn('match_id', $todayMatches)
                ->where('status', 'confirmed')
                ->get();

            $reserved = $reservedBookings->sum('guests_count');

            // Available seats
            $available = max(0, $capacity - $currentGuests - $reserved);

            // Occupied percentage
            $occupiedPercentage = $capacity > 0
                ? round(($currentGuests / $capacity) * 100, 1)
                : 0;

            // Peak times today (from checked_in timestamps)
            $peakTimesToday = $this->calculatePeakTimesToday($branch);

            // Average stay time (based on match durations or default 2 hours)
            $avgStayTime = $this->calculateAvgStayTime($branch);

            // Total visitors today (all checked_in bookings for today)
            $todayTotalVisitors = Booking::where('branch_id', $branch->id)
                ->where('status', 'checked_in')
                ->whereDate('checked_in_at', now()->toDateString())
                ->sum('guests_count');

            // Trend percentage (compare with yesterday)
            $trendPercentage = $this->calculateTrendPercentage($branch, $todayTotalVisitors);

            // Per-section occupancy
            $sections = $this->calculateSectionOccupancy($branch, $checkedInBookings);

            return [
                'occupied_percentage' => $occupiedPercentage,
                'current_guests' => $currentGuests,
                'capacity' => $capacity,
                'available' => $available,
                'reserved' => $reserved,
                'peak_times_today' => $peakTimesToday,
                'avg_stay_time' => $avgStayTime,
                'today_total_visitors' => $todayTotalVisitors,
                'trend_percentage' => $trendPercentage,
                'sections' => $sections,
            ];
        });
    }

    /**
     * Calculate peak times for today (hourly breakdown).
     */
    private function calculatePeakTimesToday(Branch $branch): array
    {
        // Get all checked_in bookings for today
        $bookings = Booking::where('branch_id', $branch->id)
            ->where('status', 'checked_in')
            ->whereDate('checked_in_at', now()->toDateString())
            ->with('match:id,kick_off,duration_minutes')
            ->get();

        if ($bookings->isEmpty()) {
            return [];
        }

        $capacity = $branch->total_seats;
        $hourlyOccupancy = [];

        // For each booking, calculate which hours they occupied
        foreach ($bookings as $booking) {
            $checkedInAt = $booking->checked_in_at;
            $duration = $booking->match?->duration_minutes ?? 120; // Default 2 hours
            $checkOutEstimate = $checkedInAt->copy()->addMinutes($duration);

            $currentHour = $checkedInAt->hour;
            $endHour = $checkOutEstimate->hour;

            for ($h = $currentHour; $h <= $endHour; $h++) {
                if (!isset($hourlyOccupancy[$h])) {
                    $hourlyOccupancy[$h] = 0;
                }
                $hourlyOccupancy[$h] += $booking->guests_count;
            }
        }

        // Convert to percentages and find top 3 peaks
        $peakTimes = [];
        foreach ($hourlyOccupancy as $hour => $guests) {
            $percentage = $capacity > 0 ? round(($guests / $capacity) * 100, 1) : 0;
            $peakTimes[] = [
                'hour' => $hour,
                'percentage' => $percentage,
                'guests' => $guests,
            ];
        }

        // Sort by percentage descending and take top 3
        usort($peakTimes, fn($a, $b) => $b['percentage'] <=> $a['percentage']);
        $topPeaks = array_slice($peakTimes, 0, 3);

        // Format time ranges
        return array_map(function ($peak) {
            $start = sprintf('%02d:00', $peak['hour']);
            $end = sprintf('%02d:00', ($peak['hour'] + 2) % 24);
            return [
                'time_range' => "{$start} - {$end}",
                'percentage' => $peak['percentage'],
            ];
        }, $topPeaks);
    }

    /**
     * Calculate average stay time.
     */
    private function calculateAvgStayTime(Branch $branch): string
    {
        // Get average match duration for today's matches
        $avgMinutes = GameMatch::where('branch_id', $branch->id)
            ->whereDate('match_date', now()->toDateString())
            ->avg('duration_minutes');

        if (!$avgMinutes) {
            $avgMinutes = 120; // Default 2 hours
        }

        $hours = round($avgMinutes / 60, 1);
        return "{$hours}H";
    }

    /**
     * Calculate trend compared to yesterday.
     */
    private function calculateTrendPercentage(Branch $branch, int $todayVisitors): float
    {
        $yesterdayVisitors = Booking::where('branch_id', $branch->id)
            ->where('status', 'checked_in')
            ->whereDate('checked_in_at', now()->subDay()->toDateString())
            ->sum('guests_count');

        if ($yesterdayVisitors == 0) {
            return $todayVisitors > 0 ? 100.0 : 0.0;
        }

        $trend = (($todayVisitors - $yesterdayVisitors) / $yesterdayVisitors) * 100;
        return round($trend, 1);
    }

    /**
     * Calculate per-section occupancy.
     */
    private function calculateSectionOccupancy(Branch $branch, $checkedInBookings): array
    {
        $sections = SeatingSection::where('branch_id', $branch->id)
            ->with('seats')
            ->get();

        $sectionOccupancy = [];

        foreach ($sections as $section) {
            $totalSeats = $section->total_seats;
            $occupiedSeats = 0;

            // Count seats occupied by checked-in bookings
            foreach ($checkedInBookings as $booking) {
                $seatsInSection = $booking->seats()
                    ->where('section_id', $section->id)
                    ->count();
                $occupiedSeats += $seatsInSection;
            }

            $percentage = $totalSeats > 0
                ? round(($occupiedSeats / $totalSeats) * 100, 0)
                : 0;

            $icon = $this->mapSectionTypeToIcon($section->type, $section->icon);

            $sectionOccupancy[] = [
                'id' => $section->id,
                'name' => $section->name,
                'occupied' => $occupiedSeats,
                'total' => $totalSeats,
                'percentage' => $percentage,
                'icon' => $icon,
            ];
        }

        return $sectionOccupancy;
    }

    /**
     * Map section type to icon.
     */
    private function mapSectionTypeToIcon(string $type, ?string $customIcon): string
    {
        if ($customIcon) {
            return $customIcon;
        }

        return match ($type) {
            'vip' => 'star',
            'premium' => 'premium',
            'standard' => 'screen',
            'lounge' => 'lounge',
            'dining' => 'dining',
            default => 'section',
        };
    }

    /**
     * Get historical peak times (last 7 days).
     */
    public function getHistoricalPeakTimes(Branch $branch): array
    {
        $cacheKey = "occupancy_historical_peaks_{$branch->id}";

        return Cache::remember($cacheKey, 300, function () use ($branch) {
            $bookings = Booking::where('branch_id', $branch->id)
                ->where('status', 'checked_in')
                ->where('checked_in_at', '>=', now()->subDays(7))
                ->with('match:id,duration_minutes')
                ->get();

            $capacity = $branch->total_seats;
            $hourlyStats = [];

            foreach ($bookings as $booking) {
                $hour = $booking->checked_in_at->hour;
                if (!isset($hourlyStats[$hour])) {
                    $hourlyStats[$hour] = ['guests' => 0, 'count' => 0];
                }
                $hourlyStats[$hour]['guests'] += $booking->guests_count;
                $hourlyStats[$hour]['count']++;
            }

            // Calculate averages
            $peakTimes = [];
            foreach ($hourlyStats as $hour => $stats) {
                $avgGuests = $stats['count'] > 0 ? $stats['guests'] / $stats['count'] : 0;
                $percentage = $capacity > 0 ? round(($avgGuests / $capacity) * 100, 1) : 0;

                $start = sprintf('%02d:00', $hour);
                $end = sprintf('%02d:00', ($hour + 1) % 24);

                $peakTimes[] = [
                    'time_range' => "{$start} - {$end}",
                    'percentage' => $percentage,
                    'avg_guests' => round($avgGuests, 0),
                ];
            }

            // Sort by percentage descending
            usort($peakTimes, fn($a, $b) => $b['percentage'] <=> $a['percentage']);

            return array_slice($peakTimes, 0, 10);
        });
    }

    /**
     * Get per-section breakdown (alternative endpoint).
     */
    public function getSectionBreakdown(Branch $branch): array
    {
        $cacheKey = "occupancy_sections_{$branch->id}";

        return Cache::remember($cacheKey, 120, function () use ($branch) {
            $todayMatches = GameMatch::where('branch_id', $branch->id)
                ->whereDate('match_date', now()->toDateString())
                ->whereIn('status', ['upcoming', 'live'])
                ->pluck('id');

            $checkedInBookings = Booking::whereIn('match_id', $todayMatches)
                ->where('status', 'checked_in')
                ->with('seats.section')
                ->get();

            return $this->calculateSectionOccupancy($branch, $checkedInBookings);
        });
    }

    /**
     * Update branch capacity.
     */
    public function updateCapacity(Branch $branch, int $totalCapacity): array
    {
        $branch->update(['total_seats' => $totalCapacity]);

        // Bust caches
        Cache::forget("occupancy_dashboard_{$branch->id}");
        Cache::forget("occupancy_sections_{$branch->id}");
        Cache::forget("occupancy_historical_peaks_{$branch->id}");

        return [
            'success' => true,
            'total_capacity' => $branch->total_seats,
        ];
    }
}
