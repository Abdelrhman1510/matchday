<?php

namespace App\Livewire\Platform;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Cafe;
use App\Models\Booking;
use App\Models\CafeSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
#[Layout('layouts.platform', ['title' => 'Cafes Management'])]
class CafesPage extends Component
{
    use WithPagination;

    public function placeholder()
    {
        return view('livewire.platform.placeholders.cafes');
    }

    public $search = '';
    public $cityFilter = '';
    public $subscriptionFilter = '';
    public $statusFilter = '';

    public $showDeleteModal = false;
    public $deletingCafeId = null;
    public $deletingCafeName = '';

    protected $queryString = ['search', 'cityFilter', 'subscriptionFilter', 'statusFilter'];

    public function toggleFeatured($cafeId)
    {
        $cafe = Cafe::withTrashed()->find($cafeId);
        if ($cafe) {
            $cafe->is_featured = !($cafe->is_featured ?? false);
            $cafe->save();
            session()->flash('message', 'Cafe featured status updated.');
        }
    }

    public function toggleCafeStatus($cafeId)
    {
        $cafe = Cafe::withTrashed()->find($cafeId);
        if (!$cafe)
            return;

        if ($cafe->trashed()) {
            $cafe->restore();
            session()->flash('message', "Cafe \"{$cafe->name}\" has been activated.");
        } else {
            $cafe->delete();
            session()->flash('message', "Cafe \"{$cafe->name}\" has been suspended.");
        }
        Cache::forget('cafes_stats');
        $this->dispatch('close-dropdown');
    }

    public function openDeleteModal($cafeId)
    {
        $cafe = Cafe::withTrashed()->find($cafeId);
        if ($cafe) {
            $this->deletingCafeId = $cafeId;
            $this->deletingCafeName = $cafe->name;
            $this->showDeleteModal = true;
        }
        $this->dispatch('close-dropdown');
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deletingCafeId = null;
        $this->deletingCafeName = '';
    }

    public function confirmDelete()
    {
        $cafe = Cafe::withTrashed()->find($this->deletingCafeId);
        if ($cafe) {
            $cafeName = $cafe->name;
            $cafe->forceDelete();
            Cache::forget('cafes_stats');
            session()->flash('message', "Cafe \"{$cafeName}\" has been permanently deleted.");
        }
        $this->closeDeleteModal();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingCityFilter()
    {
        $this->resetPage();
    }

    public function updatingSubscriptionFilter()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    private function getStats()
    {
        return Cache::remember('cafes_stats', 300, function () {
            $totalCafes = Cafe::count();
            $prevTotalCafes = Cafe::where('created_at', '<', Carbon::now()->subMonth())->count();

            $premiumCafes = CafeSubscription::where('status', 'active')
                ->whereHas('plan', function ($q) {
                    $q->whereIn('name', ['Premium', 'Elite', 'Pro', 'Enterprise']);
                })
                ->distinct('cafe_id')
                ->count();

            $activeBookings = Booking::whereHas('match', function ($q) {
                $q->where('match_date', '>=', now());
            })
                ->whereIn('status', ['confirmed', 'pending'])
                ->count();

            $prevActiveBookings = Booking::whereHas('match', function ($q) {
                $q->whereBetween('match_date', [
                    Carbon::now()->subMonth()->subDays(7),
                    Carbon::now()->subMonth()
                ]);
            })
                ->whereIn('status', ['confirmed', 'pending'])
                ->count();

            $avgRating = Cafe::avg('avg_rating') ?? 0;

            return [
                'total_cafes' => $totalCafes,
                'cafes_change' => $this->calculateChange($totalCafes, $prevTotalCafes),
                'premium_cafes' => $premiumCafes,
                'active_bookings' => $activeBookings,
                'bookings_change' => $this->calculateChange($activeBookings, $prevActiveBookings),
                'avg_rating' => round($avgRating, 1),
            ];
        });
    }

    private function calculateChange($current, $previous)
    {
        if ($previous == 0)
            return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function render()
    {
        $stats = $this->getStats();

        // Get unique cities for filter
        $cities = DB::table('branches')
            ->select('city')
            ->whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        $cafes = Cafe::withTrashed()->with([
            'branches' => function ($query) {
                $query->select('id', 'cafe_id', 'city', 'area', 'total_seats');
            },
            'subscriptions' => function ($query) {
                $query->where('status', 'active')->with('plan');
            }
        ])
            ->withCount([
                'branches as active_bookings' => function ($query) {
                    $query->join('bookings', 'branches.id', '=', 'bookings.branch_id')
                        ->whereIn('bookings.status', ['confirmed', 'pending']);
                }
            ])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('branches', function ($branchQuery) {
                            $branchQuery->where('city', 'like', '%' . $this->search . '%')
                                ->orWhere('area', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->cityFilter, function ($query) {
                $query->whereHas('branches', function ($branchQuery) {
                    $branchQuery->where('city', $this->cityFilter);
                });
            })
            ->when($this->subscriptionFilter, function ($query) {
                $query->whereHas('subscriptions', function ($subQuery) {
                    $subQuery->where('status', 'active')
                        ->whereHas('plan', function ($planQuery) {
                            $planQuery->where('name', $this->subscriptionFilter);
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                if ($this->statusFilter === 'active') {
                    $query->whereNull('deleted_at');
                } elseif ($this->statusFilter === 'suspended') {
                    $query->whereNotNull('deleted_at');
                }
            })
            ->latest()
            ->paginate(6);

        return view('livewire.platform.cafes-page', [
            'cafes' => $cafes,
            'stats' => $stats,
            'cities' => $cities,
        ]);
    }
}
