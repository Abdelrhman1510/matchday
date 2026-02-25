<?php

namespace App\Livewire\Platform;

use App\Models\CafeSubscription;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\SubscriptionPlan;
use App\Services\PlatformSettingsService;
use Carbon\Carbon;
use Livewire\Component;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Lazy;

#[Lazy]
#[Layout('layouts.platform', ['title' => 'Settings'])]
class SettingsPage extends Component
{
    public function placeholder()
    {
        return view('livewire.platform.placeholders.settings');
    }

    // Commission Settings
    public $commission_rate = 12;
    public $dynamic_pricing = false;

    // Currency Settings
    public $default_currency = 'SAR';
    public $multi_currency = false;
    public $auto_conversion = false;

    // Language Settings
    public $platform_language = 'en';
    public $timezone = 'Asia/Riyadh';
    public $multi_language = false;

    // Subscription Plans
    public $plans = [];
    public $mostPopularPlanId = null;



    public function mount()
    {
        $this->loadSettings();
        $this->loadPlans();
        // Apply saved locale immediately
        $locale = session('platform_locale', $this->platform_language);
        \App::setLocale($locale);
    }

    private function loadSettings(): void
    {
        $service = app(PlatformSettingsService::class);

        $this->commission_rate = $service->get('commission_rate', 12);
        $this->dynamic_pricing = $service->get('dynamic_pricing', false);
        $this->default_currency = $service->get('default_currency', 'SAR');
        $this->multi_currency = $service->get('multi_currency', false);
        $this->auto_conversion = $service->get('auto_conversion', false);
        $this->platform_language = $service->get('platform_language', 'en');
        $this->timezone = $service->get('timezone', 'Asia/Riyadh');
        $this->multi_language = $service->get('multi_language', false);
    }

    private function loadPlans(): void
    {
        $this->plans = SubscriptionPlan::orderBy('price', 'asc')->get();

        // Find the plan with the most active cafe subscriptions
        $popular = CafeSubscription::where('status', 'active')
            ->where('expires_at', '>', now())
            ->selectRaw('plan_id, COUNT(*) as count')
            ->groupBy('plan_id')
            ->orderByDesc('count')
            ->first();

        $this->mostPopularPlanId = $popular?->plan_id;
    }

    // ─── Auto-save hooks (wire:model.live) ────────────────────────────────────

    public function updatedCommissionRate($value): void
    {
        app(PlatformSettingsService::class)->set('commission_rate', $value, 'float', 'commission');
        session()->flash('message', 'Commission rate updated.');
    }

    public function updatedDynamicPricing($value): void
    {
        app(PlatformSettingsService::class)->set('dynamic_pricing', $value, 'bool', 'commission');
        session()->flash('message', 'Dynamic pricing updated.');
    }

    public function updatedDefaultCurrency($value): void
    {
        app(PlatformSettingsService::class)->set('default_currency', $value, 'string', 'currency');
        session()->flash('message', 'Default currency updated.');
    }

    public function updatedMultiCurrency($value): void
    {
        app(PlatformSettingsService::class)->set('multi_currency', $value, 'bool', 'currency');
        session()->flash('message', 'Multi-currency updated.');
    }

    public function updatedAutoConversion($value): void
    {
        app(PlatformSettingsService::class)->set('auto_conversion', $value, 'bool', 'currency');
        session()->flash('message', 'Auto conversion updated.');
    }

    public function updatedPlatformLanguage($value): void
    {
        $locale = in_array($value, ['en', 'ar']) ? $value : 'en';
        app(PlatformSettingsService::class)->set('platform_language', $locale, 'string', 'language');
        session()->put('platform_locale', $locale);
        \App::setLocale($locale);
        session()->flash('message', 'Platform language updated.');
        $this->dispatch('localeChanged', locale: $locale);
    }

    public function updatedTimezone($value): void
    {
        app(PlatformSettingsService::class)->set('timezone', $value, 'string', 'language');
        session()->flash('message', 'Timezone updated.');
    }

    public function updatedMultiLanguage($value): void
    {
        app(PlatformSettingsService::class)->set('multi_language', $value, 'bool', 'language');
        session()->flash('message', 'Multi-language updated.');
    }

    // ─── Footer Actions ───────────────────────────────────────────────────────

    public function saveSettings(): void
    {
        session()->flash('message', 'All settings saved successfully.');
    }

    public function resetSettings(): void
    {
        $this->loadSettings();
        session()->flash('message', 'Settings reset to saved values.');
    }

    // ─── Stats ────────────────────────────────────────────────────────────────

    private function getCommissionStats(): array
    {
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();

        $currentRevenue = Payment::where('status', 'paid')
            ->where('type', 'booking')
            ->whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->sum('amount');

        $commissionRevenue = $currentRevenue * ($this->commission_rate / 100);

        $previousRevenue = Payment::where('status', 'paid')
            ->where('type', 'booking')
            ->whereMonth('created_at', $previousMonth->month)
            ->whereYear('created_at', $previousMonth->year)
            ->sum('amount');

        $previousCommission = $previousRevenue * ($this->commission_rate / 100);

        $growthRate = $previousCommission > 0
            ? (($commissionRevenue - $previousCommission) / $previousCommission) * 100
            : 0;

        $totalBookings = Booking::whereMonth('created_at', $currentMonth->month)
            ->whereYear('created_at', $currentMonth->year)
            ->count();

        return [
            'current_rate' => $this->commission_rate,
            'monthly_revenue' => $commissionRevenue,
            'growth_rate' => round($growthRate, 1),
            'total_bookings' => $totalBookings,
        ];
    }

    public function render()
    {
        return view('livewire.platform.settings-page', [
            'stats' => $this->getCommissionStats(),
        ]);
    }
}
