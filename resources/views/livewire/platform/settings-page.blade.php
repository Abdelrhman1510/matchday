<div class="space-y-6 pb-24">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row justify-between items-center gap-4 mb-8">
        <div class="text-center lg:text-left">
            <h1 class="text-3xl sm:text-4xl font-black font-bungee text-white uppercase tracking-wider mb-1"
                style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">{{ __('platform.settings.title') }}</h1>
            <p class="text-sm text-slate-400">{{ __('platform.settings.subtitle') }}</p>
        </div>
        <div class="flex items-center justify-center lg:justify-end gap-3">
            <button
                class="w-[42px] h-[42px] flex items-center justify-center border border-[#1e164e] bg-[#0a0524] text-slate-400 hover:text-white hover:bg-[#1a0e40] rounded-lg transition-colors relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                    </path>
                </svg>
            </button>
            <div
                class="w-10 h-10 rounded-full bg-[#1a0e40] border-2 border-[#c8ff00] overflow-hidden flex items-center justify-center">
                <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session()->has('message'))
        <div class="bg-green-600/20 border border-green-600 text-green-400 px-4 py-3 rounded-lg text-sm">
            {{ session('message') }}
        </div>
    @endif

    {{-- COMMISSION STRUCTURE --}}
    <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-base font-black font-bungee text-white uppercase tracking-wider">
                    {{ __('platform.settings.commission') }}
                </h2>
                <p class="text-xs text-slate-400 mt-1">{{ __('platform.settings.commission_subtitle') }}</p>
            </div>
            <div class="w-9 h-9 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z">
                    </path>
                </svg>
            </div>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-[#0a0524] border border-[#1e164e] rounded-lg p-4">
                <div class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">
                    {{ __('platform.settings.current_rate') }}
                </div>
                <div class="text-3xl font-black font-bungee text-[#c8ff00]">{{ $stats['current_rate'] }}%</div>
                <div class="text-[10px] text-slate-500 mt-1">{{ __('platform.settings.per_booking') }}</div>
            </div>
            <div class="bg-[#0a0524] border border-[#1e164e] rounded-lg p-4">
                <div class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">
                    {{ __('platform.settings.monthly_revenue') }}
                </div>
                <div class="text-3xl font-black font-bungee text-white">
                    {{ $default_currency }}{{ number_format($stats['monthly_revenue'], 0) }}
                </div>
                <div class="text-xs {{ $stats['growth_rate'] >= 0 ? 'text-[#c8ff00]' : 'text-red-400' }} mt-1">
                    {{ $stats['growth_rate'] > 0 ? '+' : '' }}{{ $stats['growth_rate'] }}%
                    {{ __('platform.settings.this_month') }}
                </div>
            </div>
            <div class="bg-[#0a0524] border border-[#1e164e] rounded-lg p-4">
                <div class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">
                    {{ __('platform.settings.total_bookings') }}
                </div>
                <div class="text-3xl font-black font-bungee text-white">{{ number_format($stats['total_bookings']) }}
                </div>
                <div class="text-[10px] text-slate-500 mt-1">{{ __('platform.settings.this_month') }}</div>
            </div>
        </div>

        {{-- Commission Rate Slider --}}
        <div class="space-y-4">
            <div>
                <div class="flex justify-between items-center mb-3">
                    <label
                        class="text-sm font-medium text-slate-300">{{ __('platform.settings.commission_rate_label') }}</label>
                    <span class="text-base font-black font-bungee text-[#c8ff00]">{{ $commission_rate }}%</span>
                </div>
                <input type="range" wire:model.live="commission_rate" min="5" max="25" step="0.5"
                    class="w-full h-1.5 bg-[#1a0e40] rounded-lg appearance-none cursor-pointer accent-[#c8ff00]">
                <div class="flex justify-between text-[10px] text-slate-500 mt-2">
                    <span>5%</span>
                    <span>25%</span>
                </div>
            </div>

            <div class="flex items-center justify-between p-4 bg-[#0a0524] border border-[#1e164e] rounded-lg">
                <div>
                    <div class="text-sm font-medium text-white">{{ __('platform.settings.dynamic_pricing') }}</div>
                    <div class="text-xs text-slate-400">{{ __('platform.settings.dynamic_pricing_desc') }}</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model.live="dynamic_pricing" class="sr-only peer">
                    <div
                        class="w-11 h-6 bg-[#1a0e40] border border-[#1e164e] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#c8ff00]">
                    </div>
                </label>
            </div>
        </div>
    </div>

    {{-- SUBSCRIPTION PLANS --}}
    <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-base font-black font-bungee text-white uppercase tracking-wider">
                    {{ __('platform.settings.subscription_plans') }}
                </h2>
                <p class="text-xs text-slate-400 mt-1">{{ __('platform.settings.subs_plans_subtitle') }}</p>
            </div>
            <a href="{{ route('platform.plans') }}"
                class="flex items-center gap-2 px-4 py-2 bg-[#c8ff00] hover:bg-[#d4ff33] text-black text-sm font-bold rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                    </path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z">
                    </path>
                </svg>
                {{ __('platform.common.manage_plans') }}
            </a>
        </div>

        @if($plans->isEmpty())
            <div class="text-center py-10 text-slate-500">
                <p class="text-sm">{{ __('platform.settings.no_plans') }}</p>
                <a href="{{ route('platform.plans') }}"
                    class="inline-block mt-3 px-4 py-2 bg-[#c8ff00] text-black text-sm font-bold rounded-lg hover:bg-[#d4ff33] transition-colors">
                    {{ __('platform.settings.new_plan') }}
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($plans as $plan)
                    <div
                        class="relative bg-[#0a0524] border {{ ($mostPopularPlanId && $plan->id === $mostPopularPlanId) ? 'border-[#c8ff00]' : 'border-[#1e164e]' }} rounded-xl p-5 flex flex-col {{ $plan->is_active ? '' : 'opacity-60' }}">
                        @if($mostPopularPlanId && $plan->id === $mostPopularPlanId)
                            <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                                <span
                                    class="px-4 py-1 bg-[#c8ff00] text-black text-[10px] font-black font-bungee rounded-full uppercase">{{ __('platform.common.popular') }}</span>
                            </div>
                        @endif

                        {{-- Header --}}
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-base font-bold text-white">{{ $plan->name }}</h3>
                            <span
                                class="px-2 py-0.5 text-[10px] rounded font-bold {{ $plan->is_active ? 'bg-green-900/40 text-green-400 border border-green-700/40' : 'bg-[#1a0e40] text-slate-400 border border-[#1e164e]' }}">
                                {{ $plan->is_active ? __('platform.common.active') : __('platform.common.inactive') }}
                            </span>
                        </div>

                        {{-- Price --}}
                        <div class="text-3xl font-black font-bungee text-[#c8ff00] mb-0.5">
                            {{ $default_currency }}{{ number_format($plan->price, 0) }}
                        </div>
                        <p class="text-[10px] text-slate-400 mb-4">{{ __('platform.settings.per_month') }}</p>

                        {{-- Features --}}
                        <div class="flex-1 mb-5">
                            @if(is_array($plan->features) && count($plan->features) > 0)
                                <ul class="space-y-2">
                                    @foreach($plan->features as $feature)
                                        <li class="flex items-start gap-2 text-xs text-slate-300">
                                            <svg class="w-3.5 h-3.5 text-[#c8ff00] flex-shrink-0 mt-0.5" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-xs text-slate-500 italic">{{ __('platform.settings.no_features') }}</p>
                            @endif
                        </div>

                        {{-- Edit Button → links to plan management page --}}
                        <a href="{{ route('platform.plans') }}"
                            class="w-full py-2 text-sm font-bold rounded-lg transition-colors text-center block {{ ($mostPopularPlanId && $plan->id === $mostPopularPlanId) ? 'bg-[#c8ff00] hover:bg-[#d4ff33] text-black' : 'bg-[#1a0e40] border border-[#1e164e] hover:bg-[#1e164e] text-white' }}">
                            {{ __('platform.settings.edit_plan') }}
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- CURRENCY & LANGUAGE side by side --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- CURRENCY SETTINGS --}}
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-base font-black font-bungee text-white uppercase tracking-wider">
                        {{ __('platform.settings.currency') }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">{{ __('platform.settings.currency_subtitle') }}</p>
                </div>
                <div class="w-9 h-9 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <span class="text-[#c8ff00] font-black text-base">£</span>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label
                        class="block text-xs font-medium text-slate-400 uppercase tracking-widest mb-2">{{ __('platform.settings.default_currency') }}</label>
                    <select wire:model.live="default_currency"
                        class="w-full px-4 py-2.5 bg-[#0a0524] border border-[#1e164e] rounded-lg text-white text-sm focus:border-[#c8ff00] outline-none transition-colors">
                        <option value="SAR">SAR — Saudi Riyal</option>
                        <option value="GBP">GBP — British Pound (£)</option>
                        <option value="USD">USD — US Dollar</option>
                        <option value="EUR">EUR — Euro</option>
                        <option value="AED">AED — UAE Dirham</option>
                    </select>
                </div>

                <div class="flex items-center justify-between p-4 bg-[#0a0524] border border-[#1e164e] rounded-lg">
                    <div>
                        <div class="text-sm font-medium text-white">{{ __('platform.settings.multi_currency') }}</div>
                        <div class="text-xs text-slate-400">{{ __('platform.settings.multi_currency_desc') }}</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="multi_currency" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-[#1a0e40] border border-[#1e164e] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#c8ff00]">
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-[#0a0524] border border-[#1e164e] rounded-lg">
                    <div>
                        <div class="text-sm font-medium text-white">{{ __('platform.settings.auto_conversion') }}</div>
                        <div class="text-xs text-slate-400">{{ __('platform.settings.auto_conversion_desc') }}</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="auto_conversion" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-[#1a0e40] border border-[#1e164e] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#c8ff00]">
                        </div>
                    </label>
                </div>
            </div>
        </div>

        {{-- LANGUAGE & REGION --}}
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-base font-black font-bungee text-white uppercase tracking-wider">
                        {{ __('platform.settings.language') }}
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">{{ __('platform.settings.language_subtitle') }}</p>
                </div>
                <div class="w-9 h-9 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label
                        class="block text-xs font-medium text-slate-400 uppercase tracking-widest mb-2">{{ __('platform.settings.platform_language') }}</label>
                    <select wire:model.live="platform_language"
                        class="w-full px-4 py-2.5 bg-[#0a0524] border border-[#1e164e] rounded-lg text-white text-sm focus:border-[#c8ff00] outline-none transition-colors">
                        <option value="en">English (UK)</option>
                        <option value="ar">Arabic (العربية)</option>
                        <option value="fr">French (Français)</option>
                        <option value="es">Spanish (Español)</option>
                    </select>
                </div>

                <div>
                    <label
                        class="block text-xs font-medium text-slate-400 uppercase tracking-widest mb-2">{{ __('platform.settings.timezone') }}</label>
                    <select wire:model.live="timezone"
                        class="w-full px-4 py-2.5 bg-[#0a0524] border border-[#1e164e] rounded-lg text-white text-sm focus:border-[#c8ff00] outline-none transition-colors">
                        <option value="Asia/Riyadh">AST — Riyadh (GMT+3)</option>
                        <option value="Europe/London">GMT — London (GMT+0)</option>
                        <option value="America/New_York">EST — New York (GMT-5)</option>
                        <option value="Asia/Dubai">GST — Dubai (GMT+4)</option>
                        <option value="Europe/Paris">CET — Paris (GMT+1)</option>
                    </select>
                </div>

                <div class="flex items-center justify-between p-4 bg-[#0a0524] border border-[#1e164e] rounded-lg">
                    <div>
                        <div class="text-sm font-medium text-white">{{ __('platform.settings.multi_language') }}</div>
                        <div class="text-xs text-slate-400">{{ __('platform.settings.multi_language_desc') }}</div>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="multi_language" class="sr-only peer">
                        <div
                            class="w-11 h-6 bg-[#1a0e40] border border-[#1e164e] rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#c8ff00]">
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Sticky Footer Save Bar --}}
    <div
        class="fixed bottom-0 left-0 right-0 z-40 border-t border-[#1e164e] bg-[#0a0524]/95 backdrop-blur-md px-8 py-4 flex justify-end gap-3">
        <button wire:click="resetSettings"
            class="px-6 py-2.5 bg-transparent border border-[#1e164e] text-slate-300 hover:text-white hover:border-slate-500 rounded-lg transition-colors text-sm font-semibold">
            <span wire:loading.remove wire:target="resetSettings">{{ __('platform.settings.cancel') }}</span>
            <span wire:loading wire:target="resetSettings">{{ __('platform.settings.saving') }}</span>
        </button>
        <button wire:click="saveSettings"
            class="px-6 py-2.5 bg-[#c8ff00] hover:bg-[#d4ff33] text-black rounded-lg transition-colors font-bold text-sm">
            <span wire:loading.remove wire:target="saveSettings">{{ __('platform.settings.save_changes') }}</span>
            <span wire:loading wire:target="saveSettings">{{ __('platform.settings.saving') }}</span>
        </button>
    </div>

</div>