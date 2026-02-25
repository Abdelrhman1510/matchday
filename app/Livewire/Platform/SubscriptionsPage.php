<?php

namespace App\Livewire\Platform;

use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use App\Services\ExportService;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
#[Layout('layouts.platform', ['title' => 'Subscriptions & Revenue'])]
class SubscriptionsPage extends Component
{
    public function placeholder()
    {
        return view('livewire.platform.placeholders.subscriptions');
    }

    use WithPagination;

    public $search = '';
    public $period = '6M'; // 6M, 1Y, ALL

    public function updatedPeriod()
    {
        // Re-render will pass fresh revenueTrend data to the view automatically
    }

    public function exportSubscriptions()
    {
        $stats = $this->getStats();
        $revenueTrend = $this->getRevenueTrendData();
        $plans = $this->getSubscriptionPlans();

        $data = [];

        // Section 1: Overview
        $data[] = ['SUBSCRIPTIONS & REVENUE REPORT'];
        $data[] = ['Generated:', now()->format('Y-m-d H:i:s')];
        $data[] = ['Period:', $this->period];
        $data[] = [];

        // Section 2: Key Metrics
        $data[] = ['KEY METRICS'];
        $data[] = ['Metric', 'Value', 'Change'];
        $data[] = ['Monthly Recurring Revenue (MRR)', '$' . number_format($stats['mrr'], 2), $stats['mrr_change'] . '%'];
        $data[] = ['Average Revenue Per Cafe (ARPC)', '$' . number_format($stats['arpc'], 2), $stats['arpc_change'] . '%'];
        $data[] = ['Churn Rate', $stats['churn_rate'] . '%', 'Monthly'];
        $data[] = [];

        // Section 3: Revenue Trend
        $data[] = ['REVENUE TREND'];
        $data[] = ['Month', 'Revenue'];
        foreach ($revenueTrend['labels'] as $index => $label) {
            $data[] = [$label, '$' . number_format($revenueTrend['data'][$index] ?? 0, 2)];
        }
        $data[] = [];

        // Section 4: Subscription Plans
        $data[] = ['SUBSCRIPTION PLANS'];
        $data[] = ['Plan', 'Price', 'Active Subscribers', 'Features'];
        foreach ($plans as $plan) {
            $data[] = [
                $plan->name,
                '$' . number_format((float) ($plan->price ?? 0), 2),
                $plan->active_cafes ?? 0,
                $plan->description ?? ''
            ];
        }
        $data[] = [];

        // Section 5: Active Subscriptions
        $data[] = ['ACTIVE SUBSCRIPTIONS'];
        $data[] = ['Cafe ID', 'Cafe Name', 'Plan', 'Price', 'Status', 'Started At', 'Expires At', 'Days Remaining'];

        $subscriptions = CafeSubscription::with(['cafe', 'plan'])
            ->where('status', 'active')
            ->get()
            ->map(function ($sub) {
                $daysRemaining = Carbon::now()->diffInDays($sub->expires_at, false);
                return [
                    $sub->cafe_id,
                    $sub->cafe->name ?? 'N/A',
                    $sub->plan->name ?? 'N/A',
                    '$' . number_format((float) ($sub->plan->price ?? 0), 2),
                    $sub->status,
                    $sub->created_at->format('Y-m-d'),
                    $sub->expires_at ? $sub->expires_at->format('Y-m-d') : 'N/A',
                    $daysRemaining >= 0 ? $daysRemaining . ' days' : 'Expired'
                ];
            })
            ->toArray();

        $data = array_merge($data, $subscriptions);

        $filename = 'subscriptions_revenue_' . now()->format('Y-m-d_His') . '.csv';

        return ExportService::downloadCsvFlat($filename, $data);
    }

    private function getStats()
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // Monthly Recurring Revenue (MRR)
        $currentMRR = CafeSubscription::where('status', 'active')
            ->where('expires_at', '>', $currentMonth)
            ->with('plan')
            ->get()
            ->sum(function ($subscription) {
                return $subscription->plan->price ?? 0;
            });

        $lastMRR = CafeSubscription::where('status', 'active')
            ->where('expires_at', '>', $lastMonth)
            ->where('expires_at', '<=', $currentMonth)
            ->with('plan')
            ->get()
            ->sum(function ($subscription) {
                return $subscription->plan->price ?? 0;
            });

        $mrrChange = $lastMRR > 0
            ? round((($currentMRR - $lastMRR) / $lastMRR) * 100, 1)
            : 0;

        // Average Revenue Per Cafe (ARPC)
        $activeCafes = CafeSubscription::where('status', 'active')
            ->where('expires_at', '>', $currentMonth)
            ->distinct('cafe_id')
            ->count('cafe_id');

        $currentARPC = $activeCafes > 0 ? $currentMRR / $activeCafes : 0;

        $lastActiveCafes = CafeSubscription::where('status', 'active')
            ->where('expires_at', '>', $lastMonth)
            ->where('expires_at', '<=', $currentMonth)
            ->distinct('cafe_id')
            ->count('cafe_id');

        $lastARPC = $lastActiveCafes > 0 ? $lastMRR / $lastActiveCafes : 0;

        $arpcChange = $lastARPC > 0
            ? round((($currentARPC - $lastARPC) / $lastARPC) * 100, 1)
            : 0;

        // Churn Rate (cancelled this month / total active start of month)
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $activeAtStart = CafeSubscription::where('status', 'active')
            ->where('created_at', '<', $startOfMonth)
            ->count();

        $cancelledThisMonth = CafeSubscription::where('status', 'cancelled')
            ->whereMonth('updated_at', $currentMonth->month)
            ->whereYear('updated_at', $currentMonth->year)
            ->count();

        $churnRate = $activeAtStart > 0
            ? round(($cancelledThisMonth / $activeAtStart) * 100, 1)
            : 0;

        return [
            'mrr' => $currentMRR,
            'mrr_change' => $mrrChange,
            'arpc' => $currentARPC,
            'arpc_change' => $arpcChange,
            'churn_rate' => $churnRate,
        ];
    }

    private function getRevenueTrendData()
    {
        $months = $this->period === '1Y' ? 12 : ($this->period === 'ALL' ? 24 : 6);

        $labels = [];
        $data = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $revenue = Payment::where('type', 'subscription')
                ->where('status', 'completed')
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('amount');

            $data[] = $revenue;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    private function getSubscriptionPlans()
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->withCount([
                'subscriptions as active_cafes' => function ($query) {
                    $query->where('status', 'active')
                        ->where('expires_at', '>', Carbon::now());
                }
            ])
            ->orderBy('price', 'asc')
            ->get();

        // Mark the plan with the highest active cafes count as popular
        $maxActiveCafes = $plans->max('active_cafes');
        $popularMarked = false;

        return $plans->map(function ($plan) use ($maxActiveCafes, &$popularMarked) {
            // Mark only the first plan with the highest active_cafes as popular
            if (!$popularMarked && $plan->active_cafes == $maxActiveCafes && $maxActiveCafes > 0) {
                $plan->is_popular = true;
                $popularMarked = true;
            } else {
                $plan->is_popular = false;
            }
            return $plan;
        });
    }

    public function render()
    {
        $stats = $this->getStats();
        $revenueTrend = $this->getRevenueTrendData();
        $plans = $this->getSubscriptionPlans();

        $subscriptions = CafeSubscription::with(['cafe', 'plan'])
            ->where('status', 'active')
            ->when($this->search, function ($query) {
                $query->whereHas('cafe', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('city', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('expires_at', 'asc')
            ->paginate(10);

        // Add expiring soon flag
        $subscriptions->getCollection()->transform(function ($subscription) {
            $daysUntilExpiry = Carbon::now()->diffInDays($subscription->expires_at, false);
            $subscription->is_expiring_soon = $daysUntilExpiry >= 0 && $daysUntilExpiry <= 7;
            return $subscription;
        });

        $totalActive = CafeSubscription::where('status', 'active')
            ->where('expires_at', '>', Carbon::now())
            ->count();

        return view('livewire.platform.subscriptions-page', [
            'stats' => $stats,
            'revenueTrend' => $revenueTrend,
            'plans' => $plans,
            'subscriptions' => $subscriptions,
            'totalActive' => $totalActive,
        ]);
    }
}
