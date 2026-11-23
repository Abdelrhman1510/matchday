<?php

namespace App\Livewire\Platform;

use App\Models\Cafe;
use App\Models\CommissionOverride;
use App\Models\MoyasarWebhookEvent;
use App\Models\SettingsAuditLog;
use App\Models\SubscriptionPlan;
use App\Services\Wallet\FeeSettingsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform', ['title' => 'Fees & Commissions'])]
class FeesPage extends Component
{
    public array $settings = [];
    public array $plans = [];
    public bool $showHistory = false;

    public string $overrideScope = 'plan';
    public ?int $overrideScopeId = null;
    public string $overrideType = 'percent';
    public $overrideValue = 0;
    public string $cafeSearch = '';

    public function mount(FeeSettingsService $fees): void
    {
        $this->settings = $fees->all();
        $this->loadPlans();
    }

    private function loadPlans(): void
    {
        $this->plans = SubscriptionPlan::orderBy('sort_order')->orderBy('price')->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'price' => (float) $p->price,
                'is_active' => (bool) $p->is_active,
                'sort_order' => (int) $p->sort_order,
                'is_most_popular' => (bool) ($p->is_most_popular ?? $p->slug === 'pro'),
            ])->all();
    }

    public function save(FeeSettingsService $fees): void
    {
        try {
            $this->settings = $fees->update($this->settings, auth()->user());
            session()->flash('message', __('finance.saved'));
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());
        }
    }

    public function savePlans(): void
    {
        $this->validate([
            'plans.*.price' => ['required', 'numeric', 'min:0'],
            'plans.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $popular = collect($this->plans)->firstWhere('is_most_popular', true)['id'] ?? null;
        foreach ($this->plans as $row) {
            SubscriptionPlan::whereKey($row['id'])->update([
                'price' => round((float) $row['price'], 2),
                'is_active' => (bool) $row['is_active'],
                'sort_order' => (int) $row['sort_order'],
                'is_most_popular' => $row['id'] === $popular,
            ]);
        }
        Cache::forget('subscription_plans_active');
        $this->loadPlans();
        session()->flash('message', __('finance.saved'));
    }

    public function addOverride(): void
    {
        $table = $this->overrideScope === 'plan' ? 'subscription_plans' : 'cafes';
        $this->validate([
            'overrideScope' => ['required', 'in:plan,cafe'],
            'overrideScopeId' => ['required', 'integer', "exists:{$table},id"],
            'overrideType' => ['required', 'in:percent,fixed_per_booking,fixed_per_guest'],
            'overrideValue' => array_merge(['required', 'numeric', 'min:0'], $this->overrideType === 'percent' ? ['max:100'] : []),
        ]);

        $override = CommissionOverride::updateOrCreate(
            ['scope' => $this->overrideScope, 'scope_id' => $this->overrideScopeId],
            ['type' => $this->overrideType, 'value' => $this->overrideValue],
        );
        SettingsAuditLog::create([
            'key' => "commission_override.{$override->scope}.{$override->scope_id}",
            'new' => $override->only(['type', 'value']), 'user_id' => auth()->id(),
        ]);
        $this->reset('overrideScopeId', 'overrideValue', 'cafeSearch');
        session()->flash('message', __('finance.saved'));
    }

    public function removeOverride(int $id): void
    {
        $override = CommissionOverride::findOrFail($id);
        SettingsAuditLog::create([
            'key' => "commission_override.{$override->scope}.{$override->scope_id}",
            'old' => $override->only(['type', 'value']), 'user_id' => auth()->id(),
        ]);
        $override->delete();
    }

    public function render(FeeSettingsService $fees)
    {
        $seat = 45.0;
        $feeRule = $this->previewRule($this->settings['booking_fee'] ?? []);
        $commissionRule = $this->previewRule($this->settings['commission'] ?? []);
        $serviceFee = $fees->serviceFee($seat, 1, $feeRule);
        $commission = $fees->commission($seat, 1, $commissionRule);

        $overrides = CommissionOverride::orderBy('scope')->get();
        $planNames = SubscriptionPlan::pluck('name', 'id');
        $cafeNames = Cafe::whereIn('id', $overrides->where('scope', 'cafe')->pluck('scope_id'))->pluck('name', 'id');

        return view('livewire.platform.fees-page', [
            'feePreview' => ['seat' => $seat, 'fee' => $serviceFee, 'total' => round($seat + $serviceFee, 2)],
            'commissionPreview' => ['seat' => $seat, 'net' => round($seat - $commission, 2)],
            'overrides' => $overrides,
            'planNames' => $planNames,
            'cafeNames' => $cafeNames,
            'cafeResults' => $this->overrideScope === 'cafe' && strlen($this->cafeSearch) >= 2
                ? Cafe::where('name', 'like', "%{$this->cafeSearch}%")->limit(8)->pluck('name', 'id')
                : collect(),
            'history' => $this->showHistory ? SettingsAuditLog::with('user:id,name')->latest('id')->limit(50)->get() : collect(),
            // Moyasar's 6-digit code, valid for 30 minutes, for "Verify Webhook".
            'webhookCode' => MoyasarWebhookEvent::where('type', 'webhook_verification')
                ->where('created_at', '>', now()->subMinutes(30))
                ->latest('id')
                ->first()?->payload['data']['verification_code'] ?? null,
        ]);
    }

    /** Half-typed form values must not break the live preview. */
    private function previewRule(array $rule): array
    {
        foreach (['value', 'min', 'max'] as $key) {
            $rule[$key] = is_numeric($rule[$key] ?? null) ? (float) $rule[$key] : 0.0;
        }

        return $rule + ['enabled' => false, 'type' => 'fixed'];
    }
}
