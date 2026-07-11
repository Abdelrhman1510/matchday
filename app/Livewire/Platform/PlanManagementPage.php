<?php

namespace App\Livewire\Platform;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Str;
use Livewire\Component;

class PlanManagementPage extends Component
{
    // ─── State ───────────────────────────────────────────────────────
    public $plans;
    public $showModal = false;
    public $showDeleteModal = false;
    public $isCreating = true;
    public $editingPlanId = null;
    public $planToDeleteId = null;
    public $planToDeleteName = '';

    // ─── Form Fields ─────────────────────────────────────────────────
    public $formName = '';
    public $formNameAr = '';
    public $formPrice = '';
    public $formFeatures = '';
    public $formFeaturesAr = '';
    public $formMaxBookings = '';
    public $formIsActive = true;
    public $formHasAnalytics = false;
    public $formHasBranding = false;
    public $formHasPrioritySupport = false;

    // New limit fields
    public $formMaxBranches = '';
    public $formMaxMatchesPerMonth = '';
    public $formMaxBookingsPerMonth = '';
    public $formMaxStaffMembers = '';
    public $formMaxOffers = '';
    public $formHasChat = false;
    public $formHasQrScanner = false;
    public $formHasOccupancyTracking = false;
    public $formCommissionRate = '';

    // ─── Lifecycle ───────────────────────────────────────────────────

    public function mount()
    {
        $this->loadPlans();
    }

    public function loadPlans()
    {
        $this->plans = SubscriptionPlan::orderBy('price')->get();
    }

    // ─── Modal Actions ───────────────────────────────────────────────

    public function openCreateModal()
    {
        $this->resetForm();
        $this->isCreating = true;
        $this->showModal = true;
    }

    public function openEditModal($planId)
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        $this->isCreating = false;
        $this->editingPlanId = $plan->id;
        $this->formName = $plan->name;
        $this->formNameAr = $plan->name_ar;
        $this->formPrice = $plan->price;
        $this->formFeatures = is_array($plan->features) ? implode("\n", $plan->features) : '';
        $this->formFeaturesAr = is_array($plan->features_ar) ? implode("\n", $plan->features_ar) : '';
        $this->formMaxBookings = $plan->max_bookings;
        $this->formIsActive = $plan->is_active;
        $this->formHasAnalytics = $plan->has_analytics;
        $this->formHasBranding = $plan->has_branding;
        $this->formHasPrioritySupport = $plan->has_priority_support;

        // New fields
        $this->formMaxBranches = $plan->max_branches;
        $this->formMaxMatchesPerMonth = $plan->max_matches_per_month;
        $this->formMaxBookingsPerMonth = $plan->max_bookings_per_month;
        $this->formMaxStaffMembers = $plan->max_staff_members;
        $this->formMaxOffers = $plan->max_offers;
        $this->formHasChat = $plan->has_chat;
        $this->formHasQrScanner = $plan->has_qr_scanner;
        $this->formHasOccupancyTracking = $plan->has_occupancy_tracking;
        $this->formCommissionRate = $plan->commission_rate;

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function savePlan()
    {
        $this->validate([
            'formName' => 'required|string|max:255',
            'formPrice' => 'required|numeric|min:0',
        ], [
            'formName.required' => __('platform.validation.name_required'),
            'formName.max' => __('platform.validation.name_max'),
            'formPrice.required' => __('platform.validation.price_required'),
            'formPrice.numeric' => __('platform.validation.price_numeric'),
            'formPrice.min' => __('platform.validation.price_min'),
        ]);

        $featuresArray = array_filter(
            array_map('trim', explode("\n", $this->formFeatures)),
            fn($line) => $line !== ''
        );

        $featuresArArray = array_filter(
            array_map('trim', explode("\n", $this->formFeaturesAr)),
            fn($line) => $line !== ''
        );

        $data = [
            'name' => $this->formName,
            'name_ar' => $this->formNameAr ?: null,
            'slug' => Str::slug($this->formName),
            'price' => $this->formPrice,
            'currency' => 'SAR',
            'features' => array_values($featuresArray),
            'features_ar' => count($featuresArArray) ? array_values($featuresArArray) : null,
            'max_bookings' => $this->formMaxBookings ?: null,
            'is_active' => $this->formIsActive,
            'has_analytics' => $this->formHasAnalytics,
            'has_branding' => $this->formHasBranding,
            'has_priority_support' => $this->formHasPrioritySupport,

            // New fields
            'max_branches' => $this->formMaxBranches ?: null,
            'max_matches_per_month' => $this->formMaxMatchesPerMonth ?: null,
            'max_bookings_per_month' => $this->formMaxBookingsPerMonth ?: null,
            'max_staff_members' => $this->formMaxStaffMembers ?: null,
            'max_offers' => $this->formMaxOffers ?: null,
            'has_chat' => $this->formHasChat,
            'has_qr_scanner' => $this->formHasQrScanner,
            'has_occupancy_tracking' => $this->formHasOccupancyTracking,
            'commission_rate' => $this->formCommissionRate ?: null,
        ];

        if ($this->isCreating) {
            SubscriptionPlan::create($data);
            session()->flash('message', __('platform.flash.plan_created'));
        } else {
            $plan = SubscriptionPlan::findOrFail($this->editingPlanId);
            $plan->update($data);
            session()->flash('message', __('platform.flash.plan_updated'));
        }

        $this->closeModal();
        $this->loadPlans();
    }

    // ─── Toggle Active ───────────────────────────────────────────────

    public function toggleActive($planId)
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        $plan->update(['is_active' => !$plan->is_active]);
        $this->loadPlans();
        session()->flash('message', $plan->is_active ? __('platform.flash.plan_activated') : __('platform.flash.plan_deactivated'));
    }

    // ─── Delete ──────────────────────────────────────────────────────

    public function openDeleteModal($planId)
    {
        $plan = SubscriptionPlan::findOrFail($planId);

        // Guard: can't delete plans with active subscriptions
        $activeCount = $plan->subscriptions()->where('status', 'active')->count();
        if ($activeCount > 0) {
            session()->flash('error', __('platform.flash.plan_delete_blocked', ['name' => $plan->name, 'count' => $activeCount]));
            return;
        }

        $this->planToDeleteId = $plan->id;
        $this->planToDeleteName = $plan->name;
        $this->showDeleteModal = true;
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->planToDeleteId = null;
        $this->planToDeleteName = '';
    }

    public function confirmDelete()
    {
        if ($this->planToDeleteId) {
            SubscriptionPlan::destroy($this->planToDeleteId);
            session()->flash('message', __('platform.flash.plan_deleted', ['name' => $this->planToDeleteName]));
        }

        $this->cancelDelete();
        $this->loadPlans();
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    private function resetForm()
    {
        $this->editingPlanId = null;
        $this->formName = '';
        $this->formNameAr = '';
        $this->formPrice = '';
        $this->formFeatures = '';
        $this->formFeaturesAr = '';
        $this->formMaxBookings = '';
        $this->formIsActive = true;
        $this->formHasAnalytics = false;
        $this->formHasBranding = false;
        $this->formHasPrioritySupport = false;
        $this->formMaxBranches = '';
        $this->formMaxMatchesPerMonth = '';
        $this->formMaxBookingsPerMonth = '';
        $this->formMaxStaffMembers = '';
        $this->formMaxOffers = '';
        $this->formHasChat = false;
        $this->formHasQrScanner = false;
        $this->formHasOccupancyTracking = false;
        $this->formCommissionRate = '';
    }

    public function render()
    {
        return view('livewire.platform.plan-management-page')
            ->layout('layouts.platform');
    }
}
