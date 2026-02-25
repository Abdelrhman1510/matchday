<?php

namespace App\Livewire\Platform;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Cafe;
use App\Models\Booking;
use App\Models\User;
use App\Models\Payment;
use App\Models\GameMatch;
use App\Models\CafeSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\ExportService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
#[Layout('layouts.platform', ['title' => 'Owner Overview'])]
class DashboardPage extends Component
{
    use WithPagination;

    public $period = 'last_30_days';
    public $chartPeriod = 'month';
    public $searchMatches = '';
    public $periodUpdateCount = 0; // Force re-render on period change
    public $showAllActivity = false; // Toggle to show all activity

    public function placeholder()
    {
        return view('livewire.platform.placeholders.dashboard');
    }

    public function exportData()
    {
        [$startDate, $endDate] = $this->getPeriodDates();
        [$prevStart, $prevEnd] = $this->getPreviousPeriodDates();

        $output = fopen('php://temp', 'r+');

        // Section 1: Overview Statistics
        fputcsv($output, ['=== OVERVIEW STATISTICS ===']);
        fputcsv($output, ['Metric', 'Value', 'Change %', 'Period']);

        // Get stats
        $totalUsers = User::where('role', 'fan')->count();
        $totalCafes = Cafe::count();
        $activeMatches = GameMatch::whereIn('status', ['live', 'upcoming'])
            ->where('match_date', '>=', now())
            ->count();
        $monthlyRevenue = Payment::whereIn('status', ['paid', 'completed'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        $prevUsers = User::where('role', 'fan')->where('created_at', '<', $startDate)->count();
        $prevCafes = Cafe::where('created_at', '<', $startDate)->count();
        $prevRevenue = Payment::whereIn('status', ['paid', 'completed'])
            ->whereBetween('created_at', [$prevStart, $prevEnd])
            ->sum('amount');

        fputcsv($output, ['Total Users', number_format($totalUsers), $this->calculateChange($totalUsers, $prevUsers) . '%', $this->period]);
        fputcsv($output, ['Total Cafes', number_format($totalCafes), $this->calculateChange($totalCafes, $prevCafes) . '%', $this->period]);
        fputcsv($output, ['Active Matches', number_format($activeMatches), 'N/A', $this->period]);
        fputcsv($output, ['Monthly Revenue', '$' . number_format($monthlyRevenue, 2), $this->calculateChange($monthlyRevenue, $prevRevenue) . '%', $this->period]);
        fputcsv($output, []);

        // Section 2: Top Performing Cafes
        fputcsv($output, ['=== TOP PERFORMING CAFES ===']);
        fputcsv($output, ['Rank', 'Cafe Name', 'Bookings Count', 'Revenue ($)']);

        $topCafes = Cafe::withCount([
            'branches as bookings_count' => function ($query) use ($startDate, $endDate) {
                $query->join('bookings', 'branches.id', '=', 'bookings.branch_id')
                    ->whereBetween('bookings.created_at', [$startDate, $endDate]);
            }
        ])
            ->with([
                'branches' => function ($query) use ($startDate, $endDate) {
                    $query->withSum([
                        'bookings as revenue' => function ($q) use ($startDate, $endDate) {
                            $q->join('payments', 'bookings.id', '=', 'payments.booking_id')
                                ->whereIn('payments.status', ['paid', 'completed'])
                                ->whereBetween('payments.created_at', [$startDate, $endDate]);
                        }
                    ], 'payments.amount');
                }
            ])
            ->orderBy('bookings_count', 'desc')
            ->limit(10)
            ->get();

        $rank = 1;
        foreach ($topCafes as $cafe) {
            $revenue = $cafe->branches->sum('revenue') ?? 0;
            fputcsv($output, [$rank++, $cafe->name, $cafe->bookings_count, number_format($revenue, 2)]);
        }
        fputcsv($output, []);

        // Section 3: User Statistics
        fputcsv($output, ['=== USER STATISTICS ===']);
        fputcsv($output, ['Metric', 'Count', 'Percentage']);

        $totalFans = User::where('role', 'fan')->count();
        $activeUsers = User::where('role', 'fan')->where('is_active', true)->count();
        $premiumUsers = CafeSubscription::whereHas('plan', function ($q) {
            $q->where('name', 'Premium');
        })->where('status', 'active')->count();
        $newThisMonth = User::where('role', 'fan')
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()])
            ->count();

        fputcsv($output, ['Total Users', number_format($totalFans), '100%']);
        fputcsv($output, ['Active Users', number_format($activeUsers), $totalFans > 0 ? round(($activeUsers / $totalFans) * 100, 1) . '%' : '0%']);
        fputcsv($output, ['Premium Users', number_format($premiumUsers), $totalFans > 0 ? round(($premiumUsers / $totalFans) * 100, 1) . '%' : '0%']);
        fputcsv($output, ['New This Month', number_format($newThisMonth), $totalFans > 0 ? round(($newThisMonth / $totalFans) * 100, 1) . '%' : '0%']);
        fputcsv($output, []);

        // Section 4: Recent Matches
        fputcsv($output, ['=== RECENT MATCHES ===']);
        fputcsv($output, ['Match ID', 'Cafe', 'Date', 'Teams', 'Duration (min)', 'Revenue ($)', 'Status']);

        $recentMatches = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->latest('match_date')
            ->limit(20)
            ->get();

        foreach ($recentMatches as $match) {
            fputcsv($output, [
                'M-' . str_pad($match->id, 4, '0', STR_PAD_LEFT),
                $match->branch->cafe->name ?? 'N/A',
                $match->match_date ? Carbon::parse($match->match_date)->format('Y-m-d H:i') : 'N/A',
                ($match->homeTeam->name ?? 'TBD') . ' vs ' . ($match->awayTeam->name ?? 'TBD'),
                $match->duration_minutes ?? 90,
                number_format(($match->total_revenue ?? 0) / 100, 2),
                ucfirst($match->status ?? 'scheduled')
            ]);
        }
        fputcsv($output, []);

        // Section 5: Recent Activity
        fputcsv($output, ['=== RECENT ACTIVITY ===']);
        fputcsv($output, ['Type', 'Title', 'Details', 'Time']);

        $recentActivity = $this->getRecentActivity();
        foreach ($recentActivity as $activity) {
            fputcsv($output, [
                ucfirst($activity['type']),
                $activity['title'],
                $activity['subtitle'],
                $activity['time']
            ]);
        }
        fputcsv($output, []);

        // Section 6: Bookings Chart Data
        fputcsv($output, ['=== BOOKINGS TREND (Last 30 Days) ===']);
        fputcsv($output, ['Date', 'Bookings Count']);

        $bookingsData = Booking::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [Carbon::now()->subDays(29), Carbon::now()])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        foreach ($bookingsData as $data) {
            fputcsv($output, [Carbon::parse($data->date)->format('Y-m-d'), $data->count]);
        }
        fputcsv($output, []);

        // Section 7: Revenue Chart Data
        fputcsv($output, ['=== REVENUE TREND (This Year) ===']);
        fputcsv($output, ['Month', 'Revenue ($)']);

        $revenueData = Payment::selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->where('status', 'paid')
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        foreach ($revenueData as $data) {
            fputcsv($output, [
                Carbon::create()->month($data->month)->format('F'),
                number_format($data->total / 100, 2)
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        $filename = 'dashboard_complete_report_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($csv) {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function updatedPeriod()
    {
        // Clear all period caches to ensure fresh data
        Cache::forget('dashboard_stats_last_7_days');
        Cache::forget('dashboard_stats_last_30_days');
        Cache::forget('dashboard_stats_this_month');
        Cache::forget('dashboard_stats_this_year');

        // Increment counter to force component re-render
        $this->periodUpdateCount++;

        // The component will automatically re-render due to wire:model.live
        // Charts will be updated via the render method
    }

    public function updatedChartPeriod()
    {
        $this->dispatch('updateChart', period: $this->chartPeriod);
    }

    public function toggleActivityView()
    {
        $this->showAllActivity = !$this->showAllActivity;
    }

    private function getPeriodDates()
    {
        return match ($this->period) {
            'last_7_days' => [Carbon::now()->subDays(7), Carbon::now()],
            'last_30_days' => [Carbon::now()->subDays(30), Carbon::now()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()],
            'this_year' => [Carbon::now()->startOfYear(), Carbon::now()],
            default => [Carbon::now()->subDays(30), Carbon::now()],
        };
    }

    private function getPreviousPeriodDates()
    {
        [$start, $end] = $this->getPeriodDates();
        $diff = $start->diffInDays($end);
        return [$start->copy()->subDays($diff), $start];
    }

    private function calculateChange($current, $previous)
    {
        if ($previous == 0)
            return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function render()
    {
        [$startDate, $endDate] = $this->getPeriodDates();
        [$prevStart, $prevEnd] = $this->getPreviousPeriodDates();

        // Cache dashboard data for 5 minutes
        $cacheKey = 'dashboard_stats_' . $this->period;
        $stats = Cache::remember($cacheKey, 300, function () use ($startDate, $endDate, $prevStart, $prevEnd) {
            // Total counts (overall platform stats)
            $totalUsers = User::where('role', 'fan')->count();
            $totalCafes = Cafe::count();
            $activeMatches = GameMatch::whereIn('status', ['live', 'upcoming'])
                ->where('match_date', '>=', now())
                ->count();

            // Revenue for current period
            $monthlyRevenue = Payment::whereIn('status', ['paid', 'completed'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->sum('amount');

            // Previous period data for change calculations
            $prevUsers = User::where('role', 'fan')
                ->where('created_at', '<', $startDate)
                ->count();
            $prevCafes = Cafe::where('created_at', '<', $startDate)->count();
            $prevRevenue = Payment::whereIn('status', ['paid', 'completed'])
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->sum('amount');

            return [
                'total_users' => $totalUsers,
                'total_cafes' => $totalCafes,
                'active_matches' => $activeMatches,
                'monthly_revenue' => $monthlyRevenue,
                'users_change' => $this->calculateChange($totalUsers, $prevUsers),
                'cafes_change' => $this->calculateChange($totalCafes, $prevCafes),
                'revenue_change' => $this->calculateChange($monthlyRevenue, $prevRevenue),
            ];
        });

        // Bookings over time (last 7 days for mini charts)
        $bookingsData = Booking::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [Carbon::now()->subDays(6), Carbon::now()])
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Chart data - Bookings over time
        $bookingsChartData = $this->getBookingsChartData();

        // Chart data - Revenue growth
        $revenueChartData = $this->getRevenueChartData();

        // Top performing cafes
        $topCafes = Cafe::withCount([
            'branches as bookings_count' => function ($query) use ($startDate, $endDate) {
                $query->join('bookings', 'branches.id', '=', 'bookings.branch_id')
                    ->whereBetween('bookings.created_at', [$startDate, $endDate]);
            }
        ])
            ->with([
                'branches' => function ($query) use ($startDate, $endDate) {
                    $query->withSum([
                        'bookings as revenue' => function ($q) use ($startDate, $endDate) {
                            $q->join('payments', 'bookings.id', '=', 'payments.booking_id')
                                ->whereIn('payments.status', ['paid', 'completed'])
                                ->whereBetween('payments.created_at', [$startDate, $endDate]);
                        }
                    ], 'payments.amount');
                }
            ])
            ->orderBy('bookings_count', 'desc')
            ->limit(4)
            ->get();

        // User statistics
        $userStats = [
            'active_users' => User::where('role', 'fan')->where('is_active', true)->count(),
            'premium_users' => CafeSubscription::whereHas('plan', function ($q) {
                $q->where('name', 'Premium');
            })->where('status', 'active')->count(),
            'new_this_month' => User::where('role', 'fan')
                ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()])
                ->count(),
            'total_users' => User::where('role', 'fan')->count(),
        ];

        // Recent activity
        $recentActivity = $this->getRecentActivity($this->showAllActivity ? 20 : 5);

        // Recent matches
        $recentMatches = GameMatch::with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->when($this->searchMatches, function ($query) {
                $query->where('id', 'like', '%' . $this->searchMatches . '%')
                    ->orWhereHas('branch.cafe', function ($q) {
                        $q->where('name', 'like', '%' . $this->searchMatches . '%');
                    });
            })
            ->latest('match_date')
            ->paginate(10);

        return view('livewire.platform.dashboard-page', [
            'stats' => $stats,
            'bookingsData' => $bookingsData,
            'bookingsChartData' => $bookingsChartData,
            'revenueChartData' => $revenueChartData,
            'topCafes' => $topCafes,
            'userStats' => $userStats,
            'recentActivity' => $recentActivity,
            'recentMatches' => $recentMatches,
        ]);
    }

    private function getBookingsChartData()
    {
        $period = $this->chartPeriod;

        if ($period === 'week') {
            $data = Booking::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [Carbon::now()->subDays(6), Carbon::now()])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('D'))->toArray(),
                'values' => $data->pluck('count')->toArray(),
            ];
        } elseif ($period === 'year') {
            $data = Booking::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
                ->whereYear('created_at', Carbon::now()->year)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            return [
                'labels' => $data->pluck('month')->map(fn($m) => Carbon::create()->month($m)->format('M'))->toArray(),
                'values' => $data->pluck('count')->toArray(),
            ];
        } else { // month
            $data = Booking::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->whereBetween('created_at', [Carbon::now()->subDays(29), Carbon::now()])
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            return [
                'labels' => $data->pluck('date')->map(fn($d) => Carbon::parse($d)->format('M j'))->toArray(),
                'values' => $data->pluck('count')->toArray(),
            ];
        }
    }

    private function getRevenueChartData()
    {
        $data = Payment::selectRaw('MONTH(created_at) as month, SUM(amount) as total')
            ->whereIn('status', ['paid', 'completed'])
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'labels' => $data->pluck('month')->map(fn($m) => Carbon::create()->month($m)->format('M'))->toArray(),
            'values' => $data->pluck('total')->toArray(),
        ];
    }

    private function getRecentActivity($limit = 5)
    {
        $activities = [];

        // Recent cafes
        $newCafes = Cafe::latest()->limit($limit > 5 ? 5 : 2)->get()->map(function ($cafe) {
            return [
                'type' => 'cafe',
                'icon' => 'check',
                'color' => 'green',
                'title' => 'New cafe registered',
                'subtitle' => $cafe->name,
                'time' => $cafe->created_at->diffForHumans(),
                'timestamp' => $cafe->created_at,
            ];
        });

        // Recent matches completed
        $completedMatches = GameMatch::where('status', 'completed')
            ->with('branch.cafe')
            ->latest('updated_at')
            ->limit($limit > 5 ? 5 : 2)
            ->get()
            ->map(function ($match) {
                return [
                    'type' => 'match',
                    'icon' => 'trophy',
                    'color' => 'orange',
                    'title' => 'Match completed',
                    'subtitle' => $match->branch->cafe->name ?? 'Unknown',
                    'time' => $match->updated_at->diffForHumans(),
                    'timestamp' => $match->updated_at,
                ];
            });

        // Recent subscriptions
        $subscriptions = CafeSubscription::where('status', 'active')
            ->with(['cafe', 'plan'])
            ->latest('updated_at')
            ->limit($limit > 5 ? 3 : 1)
            ->get()
            ->map(function ($sub) {
                return [
                    'type' => 'subscription',
                    'icon' => 'star',
                    'color' => 'purple',
                    'title' => 'Premium subscription',
                    'subtitle' => $sub->cafe->name . ' upgraded',
                    'time' => $sub->updated_at->diffForHumans(),
                    'timestamp' => $sub->updated_at,
                ];
            });

        // Recent payments
        $payments = Payment::where('status', 'completed')
            ->with('booking.branch.cafe')
            ->latest()
            ->limit($limit > 5 ? 5 : 2)
            ->get()
            ->map(function ($payment) {
                return [
                    'type' => 'payment',
                    'icon' => 'dollar',
                    'color' => 'green',
                    'title' => 'Payment received',
                    'subtitle' => '$' . number_format($payment->amount, 2) . ' from ' . ($payment->booking->branch->cafe->name ?? 'Unknown'),
                    'time' => $payment->created_at->diffForHumans(),
                    'timestamp' => $payment->created_at,
                ];
            });

        $activities = collect()
            ->merge($newCafes)
            ->merge($completedMatches)
            ->merge($subscriptions)
            ->merge($payments)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return $activities;
    }
}
