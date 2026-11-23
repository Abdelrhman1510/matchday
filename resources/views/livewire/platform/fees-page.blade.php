@php
    $card = 'bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 space-y-4';
    $input = 'w-full bg-[#160d45] border border-[#1e164e] rounded-lg px-3 py-2 text-white text-sm focus:border-[#c8ff00] focus:outline-none';
    $label = 'block text-xs text-slate-400 mb-1';
    $h2 = 'text-lg font-bold text-white';
    $primary = 'px-5 py-2.5 bg-[#c8ff00] hover:bg-[#d4ff33] text-black font-bold text-sm rounded-lg transition-colors';
    $secondary = 'px-4 py-2 border border-[#1e164e] hover:border-slate-500 text-white text-sm rounded-lg transition-colors';
@endphp
<div class="space-y-6 pb-24">
    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-black font-bungee text-white uppercase tracking-wider mb-1">{{ __('finance.fees_title') }}</h1>
            <p class="text-sm text-slate-400">{{ __('finance.fees_subtitle') }}</p>
        </div>
        <div class="flex gap-3">
            <button wire:click="$toggle('showHistory')" class="{{ $secondary }}">{{ __('finance.history') }}</button>
            <button wire:click="save" wire:confirm="{{ __('finance.save_confirm') }}" class="{{ $primary }}">{{ __('finance.save') }}</button>
        </div>
    </div>

    @if (session()->has('message'))
        <div class="bg-green-600/20 border border-green-600 text-green-400 px-4 py-3 rounded-lg text-sm">{{ session('message') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-red-600/20 border border-red-600 text-red-400 px-4 py-3 rounded-lg text-sm">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif
    @if ($webhookCode)
        <div class="bg-[#c8ff00]/10 border border-[#c8ff00] text-white px-4 py-3 rounded-lg text-sm">
            {{ __('finance.webhook_code') }}: <span class="font-mono font-bold text-lg">{{ $webhookCode }}</span>
        </div>
    @endif

    @if ($showHistory)
        <div class="{{ $card }}">
            <h2 class="{{ $h2 }}">{{ __('finance.history') }}</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-slate-300">
                    <thead class="text-xs text-slate-400"><tr><th class="text-start p-2">{{ __('finance.when') }}</th><th class="text-start p-2">{{ __('finance.who') }}</th><th class="text-start p-2">{{ __('finance.setting') }}</th><th class="text-start p-2">{{ __('finance.old') }}</th><th class="text-start p-2">{{ __('finance.new') }}</th></tr></thead>
                    <tbody>
                        @forelse ($history as $row)
                            <tr class="border-t border-[#1e164e]">
                                <td class="p-2 whitespace-nowrap">{{ $row->created_at?->format('Y-m-d H:i') }}</td>
                                <td class="p-2">{{ $row->user?->name ?? '—' }}</td>
                                <td class="p-2 font-mono text-xs">{{ $row->key }}</td>
                                <td class="p-2 font-mono text-xs break-all">{{ json_encode($row->old, JSON_UNESCAPED_UNICODE) }}</td>
                                <td class="p-2 font-mono text-xs break-all">{{ json_encode($row->new, JSON_UNESCAPED_UNICODE) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="p-4 text-center text-slate-500">{{ __('finance.none') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="{{ $card }}">
            <label class="flex items-center justify-between"><span class="{{ $h2 }}">{{ __('finance.online_payments') }}</span><input type="checkbox" wire:model.live="settings.payments.online_enabled" class="w-5 h-5 accent-[#c8ff00]"></label>
            <p class="text-sm text-amber-400">{{ __('finance.online_warning') }}</p>
            <div><label class="{{ $label }}">{{ __('finance.hold_minutes') }}</label><input type="number" min="1" wire:model="settings.booking.payment_hold_minutes" class="{{ $input }}"></div>
        </div>

        <div class="{{ $card }}">
            <label class="flex items-center justify-between"><span class="{{ $h2 }}">{{ __('finance.booking_fee') }}</span><input type="checkbox" wire:model.live="settings.booking_fee.enabled" class="w-5 h-5 accent-[#c8ff00]"></label>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="{{ $label }}">{{ __('finance.type') }}</label><select wire:model.live="settings.booking_fee.type" class="{{ $input }}"><option value="fixed">{{ __('finance.fixed') }}</option><option value="percent">{{ __('finance.percent') }}</option><option value="per_guest">{{ __('finance.per_guest') }}</option></select></div>
                <div><label class="{{ $label }}">{{ __('finance.value') }}</label><input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="settings.booking_fee.value" class="{{ $input }}"></div>
                @if (($settings['booking_fee']['type'] ?? '') === 'percent')
                    <div><label class="{{ $label }}">{{ __('finance.min') }}</label><input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="settings.booking_fee.min" class="{{ $input }}"></div>
                    <div><label class="{{ $label }}">{{ __('finance.max') }}</label><input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="settings.booking_fee.max" class="{{ $input }}"></div>
                @endif
            </div>
            <p class="text-sm text-[#c8ff00]">{{ __('finance.fee_preview', ['seat' => number_format($feePreview['seat'], 2), 'fee' => number_format($feePreview['fee'], 2), 'total' => number_format($feePreview['total'], 2)]) }}</p>
        </div>

        <div class="{{ $card }} xl:col-span-2">
            <label class="flex items-center justify-between"><span class="{{ $h2 }}">{{ __('finance.commission') }}</span><input type="checkbox" wire:model.live="settings.commission.enabled" class="w-5 h-5 accent-[#c8ff00]"></label>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
                <div><label class="{{ $label }}">{{ __('finance.type') }}</label><select wire:model.live="settings.commission.type" class="{{ $input }}"><option value="percent">{{ __('finance.percent') }}</option><option value="fixed_per_booking">{{ __('finance.fixed_per_booking') }}</option><option value="fixed_per_guest">{{ __('finance.fixed_per_guest') }}</option></select></div>
                <div><label class="{{ $label }}">{{ __('finance.value') }}</label><input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="settings.commission.value" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">{{ __('finance.min') }}</label><input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="settings.commission.min" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">{{ __('finance.max') }}</label><input type="number" step="0.01" min="0" wire:model.live.debounce.300ms="settings.commission.max" class="{{ $input }}"></div>
            </div>
            <p class="text-sm text-[#c8ff00]">{{ __('finance.commission_preview', ['net' => number_format($commissionPreview['net'], 2), 'seat' => number_format($commissionPreview['seat'], 2)]) }}</p>

            <div class="border-t border-[#1e164e] pt-4 space-y-3">
                <h3 class="text-sm font-bold text-white">{{ __('finance.overrides') }}</h3>
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                    <div><label class="{{ $label }}">{{ __('finance.scope') }}</label><select wire:model.live="overrideScope" class="{{ $input }}"><option value="plan">{{ __('finance.plan') }}</option><option value="cafe">{{ __('finance.cafe') }}</option></select></div>
                    <div>
                        <label class="{{ $label }}">{{ $overrideScope === 'plan' ? __('finance.plan') : __('finance.cafe') }}</label>
                        @if ($overrideScope === 'plan')
                            <select wire:model="overrideScopeId" class="{{ $input }}"><option value="">—</option>@foreach ($planNames as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
                        @else
                            <input type="search" wire:model.live.debounce.300ms="cafeSearch" placeholder="{{ __('finance.search_cafe') }}" class="{{ $input }}">
                            @if ($cafeResults->isNotEmpty())
                                <select wire:model="overrideScopeId" class="{{ $input }} mt-1"><option value="">—</option>@foreach ($cafeResults as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select>
                            @endif
                        @endif
                    </div>
                    <div><label class="{{ $label }}">{{ __('finance.type') }}</label><select wire:model.live="overrideType" class="{{ $input }}"><option value="percent">{{ __('finance.percent') }}</option><option value="fixed_per_booking">{{ __('finance.fixed_per_booking') }}</option><option value="fixed_per_guest">{{ __('finance.fixed_per_guest') }}</option></select></div>
                    <div><label class="{{ $label }}">{{ __('finance.value') }}</label><input type="number" step="0.01" min="0" wire:model="overrideValue" class="{{ $input }}"></div>
                    <button wire:click="addOverride" class="{{ $secondary }}">{{ __('finance.add') }}</button>
                </div>
                @foreach ($overrides as $o)
                    <div class="flex items-center justify-between bg-[#160d45] rounded-lg px-3 py-2 text-sm text-slate-300">
                        <span>{{ $o->scope === 'plan' ? __('finance.plan') : __('finance.cafe') }}: <b class="text-white">{{ $o->scope === 'plan' ? ($planNames[$o->scope_id] ?? $o->scope_id) : ($cafeNames[$o->scope_id] ?? $o->scope_id) }}</b> · {{ __('finance.' . $o->type) }} · {{ $o->value }}</span>
                        <button wire:click="removeOverride({{ $o->id }})" wire:confirm="{{ __('finance.remove_confirm') }}" class="text-red-400 hover:text-red-300">{{ __('finance.remove') }}</button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="{{ $card }}">
            <label class="flex items-center justify-between"><span class="{{ $h2 }}">{{ __('finance.withdrawals') }}</span><input type="checkbox" wire:model="settings.withdrawals.enabled" class="w-5 h-5 accent-[#c8ff00]"></label>
            <div class="grid grid-cols-2 gap-3">
                <div><label class="{{ $label }}">{{ __('finance.min') }}</label><input type="number" step="0.01" min="0" wire:model="settings.withdrawals.min_amount" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">{{ __('finance.max') }} (0 = {{ __('finance.no_limit') }})</label><input type="number" step="0.01" min="0" wire:model="settings.withdrawals.max_amount" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">{{ __('finance.fee') }}</label><input type="number" step="0.01" min="0" wire:model="settings.withdrawals.fee" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">{{ __('finance.hold_hours') }}</label><input type="number" min="0" wire:model="settings.withdrawals.hold_hours" class="{{ $input }}"></div>
                <div><label class="{{ $label }}">{{ __('finance.payout_mode') }}</label><select wire:model="settings.withdrawals.payout_mode" class="{{ $input }}"><option value="moyasar">{{ __('finance.mode_moyasar') }}</option><option value="manual">{{ __('finance.mode_manual') }}</option></select></div>
                <label class="flex items-center gap-2 text-sm text-slate-300 self-end pb-2"><input type="checkbox" wire:model="settings.withdrawals.one_open_request" class="accent-[#c8ff00]">{{ __('finance.one_open') }}</label>
            </div>
        </div>

        <div class="space-y-6">
            <div class="{{ $card }}">
                <label class="flex items-center justify-between"><span class="{{ $h2 }}">{{ __('finance.vat') }}</span><input type="checkbox" wire:model="settings.vat.enabled" class="w-5 h-5 accent-[#c8ff00]"></label>
                <div><label class="{{ $label }}">{{ __('finance.rate') }}</label><input type="number" step="0.01" min="0" max="100" wire:model="settings.vat.rate" class="{{ $input }}"></div>
            </div>
            <div class="{{ $card }}">
                <h2 class="{{ $h2 }}">{{ __('finance.gateway_bearer') }}</h2>
                <div class="flex gap-6 text-sm text-slate-300">
                    <label class="flex items-center gap-2"><input type="radio" value="platform" wire:model="settings.gateway_fee.bearer" class="accent-[#c8ff00]">{{ __('finance.bearer_platform') }}</label>
                    <label class="flex items-center gap-2"><input type="radio" value="cafe" wire:model="settings.gateway_fee.bearer" class="accent-[#c8ff00]">{{ __('finance.bearer_cafe') }}</label>
                </div>
            </div>
        </div>

        <div class="{{ $card }} xl:col-span-2">
            <div class="flex items-center justify-between">
                <h2 class="{{ $h2 }}">{{ __('finance.subscription_plans') }}</h2>
                <label class="flex items-center gap-2 text-sm text-slate-300">{{ __('finance.subscriptions_enabled') }}<input type="checkbox" wire:model="settings.subscriptions.enabled" class="w-5 h-5 accent-[#c8ff00]"></label>
            </div>
            <p class="text-xs text-slate-400">{{ __('finance.price_note') }}</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-slate-300">
                    <thead class="text-xs text-slate-400"><tr><th class="text-start p-2">{{ __('finance.plan') }}</th><th class="text-start p-2">{{ __('finance.price') }}</th><th class="text-start p-2">{{ __('finance.order') }}</th><th class="p-2">{{ __('finance.active') }}</th><th class="p-2">{{ __('finance.most_popular') }}</th></tr></thead>
                    <tbody>
                        @foreach ($plans as $i => $plan)
                            <tr class="border-t border-[#1e164e]" wire:key="plan-{{ $plan['id'] }}">
                                <td class="p-2 text-white">{{ $plan['name'] }}</td>
                                <td class="p-2 w-32"><input type="number" step="0.01" min="0" wire:model="plans.{{ $i }}.price" class="{{ $input }}"></td>
                                <td class="p-2 w-24"><input type="number" min="0" wire:model="plans.{{ $i }}.sort_order" class="{{ $input }}"></td>
                                <td class="p-2 text-center"><input type="checkbox" wire:model="plans.{{ $i }}.is_active" class="accent-[#c8ff00]"></td>
                                <td class="p-2 text-center"><input type="checkbox" wire:model="plans.{{ $i }}.is_most_popular" class="accent-[#c8ff00]"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <button wire:click="savePlans" wire:confirm="{{ __('finance.save_confirm') }}" class="{{ $secondary }}">{{ __('finance.save_plans') }}</button>
        </div>
    </div>
</div>
