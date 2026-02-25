<?php

namespace App\Livewire\Platform;

use App\Models\Booking;
use App\Models\GameMatch;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
#[Layout('layouts.platform', ['title' => 'Matches Intelligence'])]
class MatchesPage extends Component
{
    public function placeholder()
    {
        return view('livewire.platform.placeholders.matches');
    }

    public $period = 30; // days

    public function updatedPeriod()
    {
        // Dispatch browser event to update charts
        $this->dispatch('period-updated');
    }

    private function getStats()
    {
        $startDate = Carbon::now()->subDays($this->period);
        $previousStartDate = Carbon::now()->subDays($this->period * 2);

        // Total Matches Watched (finished matches)
        $currentMatches = GameMatch::where('status', 'finished')
            ->where('match_date', '>=', $startDate)
            ->count();

        $previousMatches = GameMatch::where('status', 'finished')
            ->where('match_date', '>=', $previousStartDate)
            ->where('match_date', '<', $startDate)
            ->count();

        $matchesChange = $previousMatches > 0
            ? round((($currentMatches - $previousMatches) / $previousMatches) * 100, 1)
            : 0;

        // Prime Booking Time (most common hour)
        $primeHour = Booking::where('created_at', '>=', $startDate)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderByDesc('count')
            ->first();

        $primeTime = $primeHour ? Carbon::createFromTime($primeHour->hour)->format('gA') : 'N/A';

        // Capacity Rate (average occupancy)
        $capacityData = GameMatch::where('match_date', '>=', $startDate)
            ->whereHas('branch')
            ->get()
            ->map(function ($match) {
                $totalSeats = $match->branch->total_seats ?? 0;
                $bookedSeats = Booking::where('match_id', $match->id)
                    ->whereIn('status', ['confirmed', 'pending', 'checked_in'])
                    ->sum('guests_count');

                return $totalSeats > 0 ? ($bookedSeats / $totalSeats) * 100 : 0;
            });

        $capacityRate = $capacityData->count() > 0 ? round($capacityData->average(), 1) : 0;

        return [
            'total_matches' => $currentMatches,
            'matches_change' => $matchesChange,
            'prime_time' => $primeTime,
            'capacity_rate' => $capacityRate,
        ];
    }

    private function getMostWatchedMatches()
    {
        $startDate = Carbon::now()->subDays($this->period);

        $matches = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->where('match_date', '>=', $startDate)
            ->withCount([
                'bookings as total_bookings' => function ($query) {
                    $query->whereIn('status', ['confirmed', 'pending', 'checked_in']);
                }
            ])
            ->orderByDesc('total_bookings')
            ->limit(3)
            ->get()
            ->map(function ($match) {
                $totalSeats = $match->branch->total_seats ?? 1;
                $bookedSeats = Booking::where('match_id', $match->id)
                    ->whereIn('status', ['confirmed', 'pending', 'checked_in'])
                    ->sum('guests_count');

                $bookingRate = round(($bookedSeats / $totalSeats) * 100, 1);

                $revenue = Payment::whereHas('booking', function ($q) use ($match) {
                    $q->where('match_id', $match->id);
                })
                    ->whereIn('status', ['paid', 'completed'])
                    ->sum('amount');

                $match->booking_rate = $bookingRate;
                $match->revenue = $revenue / 100; // Convert cents to dollars
                $match->views = $bookedSeats; // Using booked seats as views proxy
    
                return $match;
            });

        // If no matches, return empty collection (will show "No matches" message)
        return $matches;
    }

    private function getPeakBookingTimesData()
    {
        $startDate = Carbon::now()->subDays($this->period);

        $hourlyData = Booking::where('created_at', '>=', $startDate)
            ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // Fill in missing hours with 0
        $labels = [];
        $data = [];
        for ($hour = 12; $hour <= 23; $hour++) {
            $labels[] = Carbon::createFromTime($hour)->format('gA');
            $data[] = (int) ($hourlyData[$hour] ?? 0);
        }

        // If no data, return sample data
        if (array_sum($data) === 0) {
            $data = [5, 12, 18, 25, 30, 28, 22, 15, 10, 8, 6, 4];
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getMatchToCafePerformanceData()
    {
        $startDate = Carbon::now()->subDays($this->period);

        // Get top 5 cafes by revenue
        $topCafes = DB::table('cafes')
            ->join('branches', 'cafes.id', '=', 'branches.cafe_id')
            ->join('game_matches', 'branches.id', '=', 'game_matches.branch_id')
            ->join('bookings', 'game_matches.id', '=', 'bookings.match_id')
            ->join('payments', 'bookings.id', '=', 'payments.booking_id')
            ->where('game_matches.match_date', '>=', $startDate)
            ->whereIn('payments.status', ['paid', 'completed'])
            ->select('cafes.id', 'cafes.name')
            ->groupBy('cafes.id', 'cafes.name')
            ->selectRaw('SUM(payments.amount) as total_revenue')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        // If no cafes found, return sample data
        if ($topCafes->isEmpty()) {
            return [
                'labels' => ['Sample Cafe 1', 'Sample Cafe 2', 'Sample Cafe 3'],
                'datasets' => [
                    [
                        'label' => 'Premier League',
                        'data' => [1500, 2000, 1800],
                        'backgroundColor' => '#c8ff00',
                    ],
                    [
                        'label' => 'Champions League',
                        'data' => [1200, 1800, 1500],
                        'backgroundColor' => '#3b82f6',
                    ],
                    [
                        'label' => 'La Liga',
                        'data' => [1000, 1400, 1200],
                        'backgroundColor' => '#f59e0b',
                    ]
                ]
            ];
        }

        $cafeIds = $topCafes->pluck('id')->toArray();

        // Get revenue by league for top cafes
        $leagueData = DB::table('cafes')
            ->join('branches', 'cafes.id', '=', 'branches.cafe_id')
            ->join('game_matches', 'branches.id', '=', 'game_matches.branch_id')
            ->join('bookings', 'game_matches.id', '=', 'bookings.match_id')
            ->join('payments', 'bookings.id', '=', 'payments.booking_id')
            ->whereIn('cafes.id', $cafeIds)
            ->where('game_matches.match_date', '>=', $startDate)
            ->whereIn('payments.status', ['paid', 'completed'])
            ->select('cafes.name as cafe_name', 'game_matches.league')
            ->selectRaw('SUM(payments.amount) as revenue')
            ->groupBy('cafes.name', 'game_matches.league')
            ->get();

        // Get unique leagues
        $leagues = $leagueData->pluck('league')->unique()->filter()->values()->toArray();

        // If no league data, use default leagues
        if (empty($leagues)) {
            $leagues = ['Premier League', 'Champions League', 'La Liga'];
        }

        // Prepare datasets
        $datasets = [];
        $colors = ['#c8ff00', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6'];

        foreach ($leagues as $index => $league) {
            $datasets[] = [
                'label' => $league ?? 'Other',
                'data' => $topCafes->map(function ($cafe) use ($leagueData, $league) {
                    $revenue = $leagueData
                        ->where('cafe_name', $cafe->name)
                        ->where('league', $league)
                        ->first();
                    return $revenue ? (float) ($revenue->revenue / 100) : 0;
                })->values()->toArray(),
                'backgroundColor' => $colors[$index % count($colors)],
            ];
        }

        return [
            'labels' => $topCafes->pluck('name')->toArray(),
            'datasets' => $datasets,
        ];
    }

    private function getMatchCategories()
    {
        $startDate = Carbon::now()->subDays($this->period);
        $previousStartDate = Carbon::now()->subDays($this->period * 2);

        $leagues = ['Premier League', 'Champions League', 'La Liga', 'International'];
        $categories = [];

        foreach ($leagues as $league) {
            $current = GameMatch::where('league', $league)
                ->where('match_date', '>=', $startDate)
                ->count();

            $previous = GameMatch::where('league', $league)
                ->where('match_date', '>=', $previousStartDate)
                ->where('match_date', '<', $startDate)
                ->count();

            $change = $previous > 0
                ? round((($current - $previous) / $previous) * 100, 1)
                : 0;

            $categories[] = [
                'name' => $league,
                'count' => $current,
                'change' => $change,
            ];
        }

        return $categories;
    }

    public function render()
    {
        $stats = $this->getStats();
        $mostWatchedMatches = $this->getMostWatchedMatches();
        $peakBookingTimes = $this->getPeakBookingTimesData();
        $matchToCafePerformance = $this->getMatchToCafePerformanceData();
        $matchCategories = $this->getMatchCategories();

        return view('livewire.platform.matches-page', [
            'stats' => $stats,
            'mostWatchedMatches' => $mostWatchedMatches,
            'peakBookingTimes' => $peakBookingTimes,
            'matchToCafePerformance' => $matchToCafePerformance,
            'matchCategories' => $matchCategories,
        ]);
    }
}
