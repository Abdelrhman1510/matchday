<?php

namespace App\Livewire\Platform;

use Livewire\Component;
use App\Models\Cafe;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\GameMatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
#[Layout('layouts.platform')]
class CafeDetailPage extends Component
{
    public function placeholder()
    {
        return view('livewire.platform.placeholders.cafe-detail');
    }

    public Cafe $cafe;
    public $period = 'month';

    public function mount(Cafe $cafe)
    {
        $this->cafe = $cafe->load([
            'owner',
            'branches.seatingSections',
            'subscriptions' => function ($q) {
                $q->where('status', 'active')->with('plan');
            }
        ]);
    }

    public function editCafe()
    {
        session()->flash('info', 'Cafe details can only be edited by the cafe owner. Use the owner contact information to request changes.');
    }

    public function addBranch()
    {
        session()->flash('info', 'Branches can only be added by the cafe owner through their dashboard.');
    }

    public function toggleCafeStatus()
    {
        if ($this->cafe->trashed()) {
            $this->cafe->restore();
            session()->flash('message', 'Cafe activated successfully.');
        } else {
            $this->cafe->delete();
            session()->flash('message', 'Cafe suspended successfully.');
        }
        $this->cafe = $this->cafe->fresh();
    }

    public function exportToPDF()
    {
        $performanceStats = $this->getPerformanceStats();
        $branchIds = $this->cafe->branches->pluck('id');

        // Get detailed booking data
        $bookings = Booking::with(['customer', 'match', 'branch'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()])
            ->get();

        $html = view('exports.cafe-analytics-pdf', [
            'cafe' => $this->cafe,
            'performanceStats' => $performanceStats,
            'bookings' => $bookings,
            'generatedAt' => Carbon::now()->format('F d, Y H:i')
        ])->render();

        $this->dispatch('download-pdf', html: $html, filename: 'cafe-analytics-' . $this->cafe->id . '.pdf');
    }

    public function exportToCSV()
    {
        $branchIds = $this->cafe->branches->pluck('id');

        $bookings = Booking::with(['customer', 'match', 'branch', 'payment'])
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()])
            ->get();

        $csv = "Date,Customer,Match,Branch,Guests,Status,Amount\n";

        foreach ($bookings as $booking) {
            $csv .= sprintf(
                '"%s","%s","%s","%s",%d,"%s","$%.2f"' . "\n",
                $booking->created_at->format('Y-m-d H:i'),
                $booking->customer->name ?? 'N/A',
                ($booking->match->homeTeam->name ?? 'TBD') . ' vs ' . ($booking->match->awayTeam->name ?? 'TBD'),
                $booking->branch->name ?? 'N/A',
                $booking->guests_count ?? 0,
                $booking->status,
                ($booking->payment->amount ?? 0) / 100
            );
        }

        $this->dispatch('download-csv', csv: $csv, filename: 'cafe-bookings-' . $this->cafe->id . '.csv');
    }

    public function exportBulk($format = 'pdf')
    {
        if ($format === 'pdf') {
            $this->exportToPDF();
        } else {
            $this->exportToCSV();
        }

        session()->flash('message', "Export completed successfully in {$format} format.");
    }

    private function getPerformanceStats()
    {
        $thisMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        $branchIds = $this->cafe->branches->pluck('id');

        // Total bookings this month
        $thisMonthBookings = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$thisMonthStart, Carbon::now()])
            ->count();

        $lastMonthBookings = Booking::whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        // Revenue this month
        $thisMonthRevenue = Payment::where('status', 'paid')
            ->whereHas('booking', function ($q) use ($branchIds, $thisMonthStart) {
                $q->whereIn('branch_id', $branchIds)
                    ->where('created_at', '>=', $thisMonthStart);
            })
            ->sum('amount');

        $lastMonthRevenue = Payment::where('status', 'paid')
            ->whereHas('booking', function ($q) use ($branchIds, $lastMonthStart, $lastMonthEnd) {
                $q->whereIn('branch_id', $branchIds)
                    ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd]);
            })
            ->sum('amount');

        // Occupancy rate
        $totalSeats = $this->cafe->branches->sum('total_seats');
        $bookedSeats = Booking::whereIn('branch_id', $branchIds)
            ->whereIn('status', ['confirmed', 'pending'])
            ->whereHas('match', function ($q) {
                $q->where('match_date', '>=', Carbon::now())
                    ->where('match_date', '<=', Carbon::now()->addDays(7));
            })
            ->sum('guests_count');

        $occupancyRate = $totalSeats > 0 ? ($bookedSeats / $totalSeats) * 100 : 0;

        return [
            'bookings' => $thisMonthBookings,
            'bookings_change' => $this->calculateChange($thisMonthBookings, $lastMonthBookings),
            'revenue' => $thisMonthRevenue,
            'revenue_change' => $this->calculateChange($thisMonthRevenue, $lastMonthRevenue),
            'occupancy_rate' => round($occupancyRate, 1),
            'occupancy_change' => 0, // Can calculate from previous week if needed
            'rating' => $this->cafe->avg_rating ?? 0,
            'rating_change' => 0,
        ];
    }

    private function calculateChange($current, $previous)
    {
        if ($previous == 0)
            return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getBookingsChartData()
    {
        $branchIds = $this->cafe->branches->pluck('id');

        $data = Booking::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereIn('branch_id', $branchIds)
            ->whereBetween('created_at', [Carbon::now()->subDays(29), Carbon::now()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M j'))->toArray(),
            'values' => $data->pluck('count')->toArray(),
        ];
    }

    private function getRevenueByMatchTypeData()
    {
        $branchIds = $this->cafe->branches->pluck('id');

        $data = Payment::select('game_matches.league', DB::raw('SUM(payments.amount) as total'))
            ->join('bookings', 'payments.booking_id', '=', 'bookings.id')
            ->join('game_matches', 'bookings.match_id', '=', 'game_matches.id')
            ->whereIn('bookings.branch_id', $branchIds)
            ->where('payments.status', 'paid')
            ->groupBy('game_matches.league')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return [
            'labels' => $data->pluck('league')->toArray(),
            'values' => $data->pluck('total')->map(fn($v) => $v / 100)->toArray(),
        ];
    }

    private function getUpcomingMatches()
    {
        $branchIds = $this->cafe->branches->pluck('id');

        return GameMatch::with(['homeTeam', 'awayTeam', 'branch'])
            ->whereIn('branch_id', $branchIds)
            ->where('match_date', '>=', Carbon::now())
            ->whereIn('status', ['upcoming', 'scheduled'])
            ->withCount('bookings')
            ->orderBy('match_date')
            ->limit(5)
            ->get()
            ->map(function ($match) {
                $totalSeats = $match->branch->total_seats ?? 0;
                $bookedSeats = Booking::where('match_id', $match->id)
                    ->whereIn('status', ['confirmed', 'pending'])
                    ->sum('guests_count');

                $capacityPercent = $totalSeats > 0 ? ($bookedSeats / $totalSeats) * 100 : 0;

                $expectedRevenue = Payment::where('status', 'paid')
                    ->whereHas('booking', function ($q) use ($match) {
                        $q->where('match_id', $match->id);
                    })
                    ->sum('amount');

                return [
                    'match' => $match,
                    'bookings_count' => $match->bookings_count,
                    'capacity_percent' => round($capacityPercent, 0),
                    'expected_revenue' => $expectedRevenue,
                ];
            });
    }

    private function getRevenueByBranchData()
    {
        $branches = $this->cafe->branches()->get();

        $branchRevenue = $branches->map(function ($branch) {
            $revenue = Payment::whereHas('booking', function ($query) use ($branch) {
                $query->where('branch_id', $branch->id);
            })
                ->where('status', 'completed')
                ->sum('amount');

            return [
                'name' => $branch->name,
                'revenue' => $revenue ?? 0
            ];
        });

        return [
            'labels' => $branchRevenue->pluck('name')->toArray(),
            'data' => $branchRevenue->pluck('revenue')->toArray(),
        ];
    }

    private function getMonthlyComparisonData()
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $currentMonthRevenue = Payment::whereHas('booking', function ($query) use ($currentMonth) {
            $query->whereHas('branch', function ($q) {
                $q->where('cafe_id', $this->cafe->id);
            })
                ->whereMonth('created_at', $currentMonth->month)
                ->whereYear('created_at', $currentMonth->year);
        })
            ->where('status', 'completed')
            ->sum('amount');

        $lastMonthRevenue = Payment::whereHas('booking', function ($query) use ($lastMonth) {
            $query->whereHas('branch', function ($q) {
                $q->where('cafe_id', $this->cafe->id);
            })
                ->whereMonth('created_at', $lastMonth->month)
                ->whereYear('created_at', $lastMonth->year);
        })
            ->where('status', 'completed')
            ->sum('amount');

        return [
            'labels' => [$lastMonth->format('M Y'), $currentMonth->format('M Y')],
            'currentMonth' => [$lastMonthRevenue, $currentMonthRevenue],
            'lastMonth' => [$lastMonthRevenue, 0], // For comparison visualization
        ];
    }

    public function render()
    {
        $performanceStats = $this->getPerformanceStats();
        $bookingsChartData = $this->getBookingsChartData();
        $revenueByMatchType = $this->getRevenueByMatchTypeData();
        $upcomingMatches = $this->getUpcomingMatches();
        $revenueByBranch = $this->getRevenueByBranchData();
        $monthlyComparison = $this->getMonthlyComparisonData();

        // Calculate occupancy for each branch
        $branches = $this->cafe->branches->map(function ($branch) {
            $totalSeats = $branch->total_seats ?? 0;
            $bookedSeats = Booking::where('branch_id', $branch->id)
                ->whereIn('status', ['confirmed', 'pending'])
                ->whereHas('match', function ($q) {
                    $q->where('match_date', '>=', Carbon::now())
                        ->where('match_date', '<=', Carbon::now()->addDays(7));
                })
                ->sum('guests_count');

            $branch->occupancy_rate = $totalSeats > 0 ? round(($bookedSeats / $totalSeats) * 100, 1) : 0;
            return $branch;
        });

        return view('livewire.platform.cafe-detail-page', [
            'performanceStats' => $performanceStats,
            'bookingsChartData' => $bookingsChartData,
            'revenueByMatchType' => $revenueByMatchType,
            'upcomingMatches' => $upcomingMatches,
            'branches' => $branches,
            'revenueByBranch' => $revenueByBranch,
            'monthlyComparison' => $monthlyComparison,
        ]);
    }
}
