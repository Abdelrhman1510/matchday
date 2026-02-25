<?php

namespace App\Livewire\Platform;

use App\Models\Booking;
use App\Models\LoyaltyCard;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Services\ExportService;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
#[Layout('layouts.platform', ['title' => 'Users Analytics'])]
class UsersPage extends Component
{
    public function placeholder()
    {
        return view('livewire.platform.placeholders.users');
    }
    public $period = 30; // days

    public function updatedPeriod()
    {
        // Dispatch browser event to update charts
        $this->dispatch('period-updated');
    }

    public function exportUsers()
    {
        $startDate = Carbon::now()->subDays($this->period);
        $stats = $this->getStats();
        $fanSegments = $this->getFanSegments();

        $data = [];

        // Section 1: Overview Stats
        $data[] = ['USERS ANALYTICS REPORT'];
        $data[] = ['Generated:', now()->format('Y-m-d H:i:s')];
        $data[] = ['Period:', "Last {$this->period} Days"];
        $data[] = [];

        // Section 2: Key Metrics
        $data[] = ['KEY METRICS'];
        $data[] = ['Metric', 'Value', 'Change'];
        $data[] = ['Active Users', number_format($stats['active_users']), $stats['active_users_change'] . '%'];
        $data[] = ['VIP Users', number_format($stats['vip_users']), $stats['vip_users_change'] . '%'];
        $data[] = ['New Signups', number_format($stats['new_signups']), $stats['new_signups_change'] . '%'];
        $data[] = [];

        // Section 3: Fan Segments
        $data[] = ['FAN SEGMENTS ANALYSIS'];
        $data[] = ['Segment', 'Total Users', 'Avg Bookings', 'Engagement %', 'Retention %'];
        $data[] = [
            'Casual Fans',
            number_format($fanSegments['casual']['total_users']),
            $fanSegments['casual']['avg_bookings'],
            $fanSegments['casual']['engagement'] . '%',
            $fanSegments['casual']['retention'] . '%'
        ];
        $data[] = [
            'VIP Fans',
            number_format($fanSegments['vip']['total_users']),
            $fanSegments['vip']['avg_bookings'],
            $fanSegments['vip']['engagement'] . '%',
            $fanSegments['vip']['retention'] . '%'
        ];
        $data[] = [];

        // Section 4: All Users List
        $data[] = ['ALL USERS'];
        $data[] = ['ID', 'Name', 'Email', 'Role', 'Active', 'Loyalty Tier', 'Total Bookings', 'Created At', 'Last Active'];

        $users = User::where('role', 'fan')
            ->with('loyaltyCard')
            ->withCount('bookings')
            ->get()
            ->map(function ($user) {
                return [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->is_active ? 'Yes' : 'No',
                    $user->loyaltyCard->tier ?? 'None',
                    $user->bookings_count,
                    $user->created_at->format('Y-m-d H:i:s'),
                    $user->updated_at->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();

        $data = array_merge($data, $users);

        $filename = 'users_analytics_' . now()->format('Y-m-d_His') . '.csv';

        return ExportService::downloadCsvFlat($filename, $data);
    }

    private function getStats()
    {
        $startDate = Carbon::now()->subDays($this->period);
        $previousStartDate = Carbon::now()->subDays($this->period * 2);

        // Active Users (is_active = true)
        $currentActiveUsers = User::where('is_active', true)->count();
        $previousActiveUsers = User::where('is_active', true)
            ->where('updated_at', '<', $startDate)
            ->count();

        $activeUsersChange = $previousActiveUsers > 0
            ? round((($currentActiveUsers - $previousActiveUsers) / $previousActiveUsers) * 100, 1)
            : 0;

        // VIP Users (loyalty tier = gold or platinum)
        $currentVipUsers = User::whereHas('loyaltyCard', function ($query) {
            $query->whereIn('tier', ['gold', 'platinum']);
        })->count();

        $previousVipUsers = User::whereHas('loyaltyCard', function ($query) use ($startDate) {
            $query->whereIn('tier', ['gold', 'platinum'])
                ->where('updated_at', '<', $startDate);
        })->count();

        $vipUsersChange = $previousVipUsers > 0
            ? round((($currentVipUsers - $previousVipUsers) / $previousVipUsers) * 100, 1)
            : 0;

        // New Signups
        $currentSignups = User::where('created_at', '>=', $startDate)->count();
        $previousSignups = User::where('created_at', '>=', $previousStartDate)
            ->where('created_at', '<', $startDate)
            ->count();

        $signupsChange = $previousSignups > 0
            ? round((($currentSignups - $previousSignups) / $previousSignups) * 100, 1)
            : 0;

        return [
            'active_users' => $currentActiveUsers,
            'active_users_change' => $activeUsersChange,
            'vip_users' => $currentVipUsers,
            'vip_users_change' => $vipUsersChange,
            'new_signups' => $currentSignups,
            'new_signups_change' => $signupsChange,
        ];
    }

    private function getUserGrowthData()
    {
        // Get user growth for last 6 months
        $labels = [];
        $data = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $userCount = User::where('is_active', true)
                ->whereMonth('created_at', '<=', $month->month)
                ->whereYear('created_at', '<=', $month->year)
                ->count();

            $data[] = $userCount;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getBookingBehaviorData()
    {
        $startDate = Carbon::now()->subDays($this->period);

        // Group bookings by day of week
        $dayData = Booking::where('created_at', '>=', $startDate)
            ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('count', 'day')
            ->toArray();

        $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $labels = [];
        $data = [];

        for ($day = 1; $day <= 7; $day++) {
            $labels[] = $daysOfWeek[$day - 1];
            $data[] = $dayData[$day] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getFanSegments()
    {
        // Casual Fans (< 3 bookings)
        $casualFans = User::withCount('bookings')
            ->having('bookings_count', '<', 3)
            ->get();

        $casualTotalUsers = $casualFans->count();
        $casualAvgBookings = $casualFans->avg('bookings_count') ?? 0;

        // Calculate engagement (users with at least 1 booking)
        $casualEngaged = $casualFans->filter(fn($user) => $user->bookings_count > 0)->count();
        $casualEngagement = $casualTotalUsers > 0
            ? round(($casualEngaged / $casualTotalUsers) * 100, 1)
            : 0;

        // Calculate retention (users active in last 30 days)
        $casualRetained = User::withCount('bookings')
            ->having('bookings_count', '<', 3)
            ->where('is_active', true)
            ->whereHas('bookings', function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
            })
            ->count();

        $casualRetention = $casualTotalUsers > 0
            ? round(($casualRetained / $casualTotalUsers) * 100, 1)
            : 0;

        // VIP Fans (loyalty tier gold/platinum)
        $vipFans = User::whereHas('loyaltyCard', function ($query) {
            $query->whereIn('tier', ['gold', 'platinum']);
        })->withCount('bookings')->get();

        $vipTotalUsers = $vipFans->count();
        $vipAvgBookings = $vipFans->avg('bookings_count') ?? 0;

        // VIP engagement
        $vipEngaged = $vipFans->filter(fn($user) => $user->bookings_count > 0)->count();
        $vipEngagement = $vipTotalUsers > 0
            ? round(($vipEngaged / $vipTotalUsers) * 100, 1)
            : 0;

        // VIP retention
        $vipRetained = User::whereHas('loyaltyCard', function ($query) {
            $query->whereIn('tier', ['gold', 'platinum']);
        })
            ->where('is_active', true)
            ->whereHas('bookings', function ($query) {
                $query->where('created_at', '>=', Carbon::now()->subDays(30));
            })
            ->count();

        $vipRetention = $vipTotalUsers > 0
            ? round(($vipRetained / $vipTotalUsers) * 100, 1)
            : 0;

        return [
            'casual' => [
                'total_users' => $casualTotalUsers,
                'avg_bookings' => round($casualAvgBookings, 1),
                'engagement' => $casualEngagement,
                'retention' => $casualRetention,
            ],
            'vip' => [
                'total_users' => $vipTotalUsers,
                'avg_bookings' => round($vipAvgBookings, 1),
                'engagement' => $vipEngagement,
                'retention' => $vipRetention,
            ],
        ];
    }

    public function render()
    {
        $stats = $this->getStats();
        $userGrowth = $this->getUserGrowthData();
        $bookingBehavior = $this->getBookingBehaviorData();
        $fanSegments = $this->getFanSegments();

        return view('livewire.platform.users-page', [
            'stats' => $stats,
            'userGrowth' => $userGrowth,
            'bookingBehavior' => $bookingBehavior,
            'fanSegments' => $fanSegments,
        ]);
    }
}
