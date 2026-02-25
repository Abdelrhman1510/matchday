<div class="space-y-6 pb-24">

    {{-- Header --}}
    <div class="flex flex-col lg:flex-row justify-between items-center gap-4 mb-8">
        <div class="text-center lg:text-left">
            <h1 class="text-3xl sm:text-4xl font-black font-bungee text-white uppercase tracking-wider mb-1"
                style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">{{ __('platform.plans.title') }}</h1>
            <p class="text-sm text-slate-400">{{ __('platform.plans.subtitle') }}</p>
        </div>
        <button wire:click="openCreateModal"
            class="px-5 py-2.5 bg-[#c8ff00] hover:bg-[#d4ff33] text-black font-bold text-sm rounded-lg transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            {{ __('platform.plans.new_plan') }}
        </button>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="bg-green-600/20 border border-green-600 text-green-400 px-4 py-3 rounded-lg text-sm">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="bg-red-600/20 border border-red-600 text-red-400 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
    @endif

    {{-- Plan Cards Grid --}}
    @if($plans->isEmpty())
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-12 text-center">
            <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                </path>
            </svg>
            <p class="text-slate-400 text-sm mb-4">{{ __('platform.plans.no_plans') }}</p>
            <button wire:click="openCreateModal"
                class="px-5 py-2.5 bg-[#c8ff00] hover:bg-[#d4ff33] text-black font-bold text-sm rounded-lg transition-colors">
                {{ __('platform.plans.new_plan') }}
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @foreach($plans as $plan)
                <div class="relative bg-[#0e0735] border {{ $plan->is_active ? 'border-[#1e164e]' : 'border-red-900/30' }} rounded-xl p-6 flex flex-col {{ $plan->is_active ? '' : 'opacity-60' }}">

                    {{-- Status Badge --}}
                    <div class="flex items-start justify-between mb-4">
                        <h3 class="text-lg font-bold text-white">{{ $plan->name }}</h3>
                        <span class="px-2.5 py-1 text-[10px] rounded-full font-bold {{ $plan->is_active ? 'bg-green-900/40 text-green-400 border border-green-700/40' : 'bg-red-900/30 text-red-400 border border-red-700/30' }}">
                            {{ $plan->is_active ? __('platform.common.active') : __('platform.common.inactive') }}
                        </span>
                    </div>

                    {{-- Price --}}
                    <div class="text-3xl font-black font-bungee text-[#c8ff00] mb-1">
                        SAR{{ number_format($plan->price, 0) }}
                    </div>
                    <p class="text-[10px] text-slate-500 mb-4">{{ __('platform.common.per_month') }}</p>

                    {{-- Limits Summary --}}
                    <div class="grid grid-cols-2 gap-2 mb-4">
                        <div class="bg-[#0a0524] border border-[#1e164e] rounded-lg p-2.5">
                            <div class="text-[9px] text-slate-500 uppercase tracking-wider">{{ __('platform.plans.branches') }}</div>
                            <div class="text-sm font-bold text-white">{{ $plan->max_branches ?? '∞' }}</div>
                        </div>
                        <div class="bg-[#0a0524] border border-[#1e164e] rounded-lg p-2.5">
                            <div class="text-[9px] text-slate-500 uppercase tracking-wider">{{ __('platform.plans.matches_mo') }}</div>
                            <div class="text-sm font-bold text-white">{{ $plan->max_matches_per_month ?? '∞' }}</div>
                        </div>
                        <div class="bg-[#0a0524] border border-[#1e164e] rounded-lg p-2.5">
                            <div class="text-[9px] text-slate-500 uppercase tracking-wider">{{ __('platform.plans.bookings_mo') }}</div>
                            <div class="text-sm font-bold text-white">{{ $plan->max_bookings_per_month ?? '∞' }}</div>
                        </div>
                        <div class="bg-[#0a0524] border border-[#1e164e] rounded-lg p-2.5">
                            <div class="text-[9px] text-slate-500 uppercase tracking-wider">{{ __('platform.plans.staff') }}</div>
                            <div class="text-sm font-bold text-white">{{ $plan->max_staff_members ?? '∞' }}</div>
                        </div>
                        <div class="bg-[#0a0524] border border-[#1e164e] rounded-lg p-2.5">
                            <div class="text-[9px] text-slate-500 uppercase tracking-wider">{{ __('platform.plans.offers') }}</div>
                            <div class="text-sm font-bold text-white">{{ $plan->max_offers ?? '∞' }}</div>
                        </div>
                        @if($plan->commission_rate)
                        <div class="bg-[#0a0524] border border-[#1e164e] rounded-lg p-2.5">
                            <div class="text-[9px] text-slate-500 uppercase tracking-wider">{{ __('platform.plans.commission') }}</div>
                            <div class="text-sm font-bold text-[#c8ff00]">{{ $plan->commission_rate }}%</div>
                        </div>
                        @endif
                    </div>

                    {{-- Feature Flags --}}
                    <div class="flex flex-wrap gap-1.5 mb-4">
                        @foreach([
                            'has_analytics' => __('platform.plans.analytics'),
                            'has_branding' => __('platform.plans.branding'),
                            'has_priority_support' => __('platform.plans.priority'),
                            'has_chat' => __('platform.plans.chat'),
                            'has_qr_scanner' => __('platform.plans.qr'),
                            'has_occupancy_tracking' => __('platform.plans.occupancy'),
                        ] as $flag => $label)
                            <span class="px-2 py-0.5 text-[9px] font-bold rounded-full {{ $plan->{$flag} ? 'bg-[#c8ff00]/15 text-[#c8ff00] border border-[#c8ff00]/30' : 'bg-[#0a0524] text-slate-600 border border-[#1e164e]' }}">
                                {{ $label }}
                            </span>
                        @endforeach
                    </div>

                    {{-- Features List --}}
                    <div class="flex-1 mb-5">
                        @if(is_array($plan->features) && count($plan->features) > 0)
                            <ul class="space-y-1.5">
                                @foreach(array_slice($plan->features, 0, 4) as $feature)
                                    <li class="flex items-start gap-2 text-xs text-slate-300">
                                        <svg class="w-3.5 h-3.5 text-[#c8ff00] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                                @if(count($plan->features) > 4)
                                    <li class="text-xs text-slate-500 pl-5">+{{ count($plan->features) - 4 }} more...</li>
                                @endif
                            </ul>
                        @else
                            <p class="text-xs text-slate-500 italic">{{ __('platform.plans.no_features') }}</p>
                        @endif
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex gap-2">
                        <button wire:click="openEditModal({{ $plan->id }})"
                            class="flex-1 py-2 text-sm font-bold rounded-lg bg-[#1a0e40] border border-[#1e164e] hover:bg-[#1e164e] text-white transition-colors">
                            {{ __('platform.common.edit_plan') }}
                        </button>
                        <button wire:click="toggleActive({{ $plan->id }})"
                            class="px-3 py-2 text-sm rounded-lg border transition-colors {{ $plan->is_active ? 'border-yellow-600/30 text-yellow-400 hover:bg-yellow-900/20' : 'border-green-600/30 text-green-400 hover:bg-green-900/20' }}"
                            title="{{ $plan->is_active ? 'Deactivate' : 'Activate' }}">
                            @if($plan->is_active)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @endif
                        </button>
                        <button wire:click="openDeleteModal({{ $plan->id }})"
                            class="px-3 py-2 text-sm rounded-lg border border-red-600/30 text-red-400 hover:bg-red-900/20 transition-colors"
                            title="Delete">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════
    CREATE / EDIT MODAL
    ═══════════════════════════════════════════════════════════ --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
            <div class="bg-[#1e293b] border border-slate-700 rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

                {{-- Modal Header --}}
                <div class="flex items-center justify-between p-6 border-b border-slate-700 sticky top-0 bg-[#1e293b] z-10">
                    <h3 class="text-lg font-bold text-white uppercase tracking-wide">
                        {{ $isCreating ? __('platform.plans.create_title') : __('platform.plans.edit_title') }}
                    </h3>
                    <button wire:click="closeModal" class="text-slate-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="p-6 space-y-5">

                    {{-- Plan Name + Price Row --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-white mb-1.5">{{ __('platform.plans.name') }} <span class="text-red-400">*</span></label>
                            <input type="text" wire:model="formName" placeholder="e.g. Starter, Pro, Elite"
                                class="w-full px-4 py-2.5 bg-[#0f172a] border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:border-[#c8ff00] focus:ring-1 focus:ring-[#c8ff00] outline-none">
                            @error('formName') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-white mb-1.5">{{ __('platform.plans.price') }} (SAR) <span class="text-red-400">*</span></label>
                            <input type="number" wire:model="formPrice" step="0.01" min="0" placeholder="0.00"
                                class="w-full px-4 py-2.5 bg-[#0f172a] border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:border-[#c8ff00] focus:ring-1 focus:ring-[#c8ff00] outline-none">
                            @error('formPrice') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Limits Section --}}
                    <div>
                        <h4 class="text-xs font-bold text-[#c8ff00] uppercase tracking-widest mb-3">{{ __('platform.plans.limits_section') }}</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] text-slate-400 uppercase mb-1">{{ __('platform.plans.max_branches') }}</label>
                                <input type="number" wire:model="formMaxBranches" min="0" placeholder="∞"
                                    class="w-full px-3 py-2 bg-[#0f172a] border border-slate-700 rounded-lg text-white text-sm placeholder-slate-600 focus:border-[#c8ff00] outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 uppercase mb-1">{{ __('platform.plans.max_matches') }}</label>
                                <input type="number" wire:model="formMaxMatchesPerMonth" min="0" placeholder="∞"
                                    class="w-full px-3 py-2 bg-[#0f172a] border border-slate-700 rounded-lg text-white text-sm placeholder-slate-600 focus:border-[#c8ff00] outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 uppercase mb-1">{{ __('platform.plans.max_bookings') }}</label>
                                <input type="number" wire:model="formMaxBookingsPerMonth" min="0" placeholder="∞"
                                    class="w-full px-3 py-2 bg-[#0f172a] border border-slate-700 rounded-lg text-white text-sm placeholder-slate-600 focus:border-[#c8ff00] outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 uppercase mb-1">{{ __('platform.plans.max_staff') }}</label>
                                <input type="number" wire:model="formMaxStaffMembers" min="0" placeholder="∞"
                                    class="w-full px-3 py-2 bg-[#0f172a] border border-slate-700 rounded-lg text-white text-sm placeholder-slate-600 focus:border-[#c8ff00] outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 uppercase mb-1">{{ __('platform.plans.max_offers') }}</label>
                                <input type="number" wire:model="formMaxOffers" min="0" placeholder="∞"
                                    class="w-full px-3 py-2 bg-[#0f172a] border border-slate-700 rounded-lg text-white text-sm placeholder-slate-600 focus:border-[#c8ff00] outline-none">
                            </div>
                            <div>
                                <label class="block text-[10px] text-slate-400 uppercase mb-1">{{ __('platform.plans.commission_rate') }}</label>
                                <input type="number" wire:model="formCommissionRate" min="0" max="100" step="0.01" placeholder="Default"
                                    class="w-full px-3 py-2 bg-[#0f172a] border border-slate-700 rounded-lg text-white text-sm placeholder-slate-600 focus:border-[#c8ff00] outline-none">
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2">{{ __('platform.plans.limits_hint') }}</p>
                    </div>

                    {{-- Features Text --}}
                    <div>
                        <label class="block text-sm font-medium text-white mb-1.5">{{ __('platform.plans.features_label') }} <span class="text-slate-500 font-normal">({{ __('platform.plans.one_per_line') }})</span></label>
                        <textarea wire:model="formFeatures" rows="4"
                            placeholder="Up to 3 branches&#10;Basic analytics&#10;Email support"
                            class="w-full px-4 py-2.5 bg-[#0f172a] border border-slate-700 rounded-lg text-white placeholder-slate-500 focus:border-[#c8ff00] focus:ring-1 focus:ring-[#c8ff00] outline-none resize-none text-sm"></textarea>
                    </div>

                    {{-- Feature Flags --}}
                    <div>
                        <h4 class="text-xs font-bold text-[#c8ff00] uppercase tracking-widest mb-3">{{ __('platform.plans.feature_flags') }}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach([
                                'formIsActive' => [__('platform.plans.active_toggle'), __('platform.plans.active_toggle_desc')],
                                'formHasAnalytics' => [__('platform.plans.analytics_access'), __('platform.plans.analytics_access_desc')],
                                'formHasBranding' => [__('platform.plans.custom_branding'), __('platform.plans.custom_branding_desc')],
                                'formHasPrioritySupport' => [__('platform.plans.priority_support'), __('platform.plans.priority_support_desc')],
                                'formHasChat' => [__('platform.plans.chat_access'), __('platform.plans.chat_access_desc')],
                                'formHasQrScanner' => [__('platform.plans.qr_scanner'), __('platform.plans.qr_scanner_desc')],
                                'formHasOccupancyTracking' => [__('platform.plans.occupancy_tracking'), __('platform.plans.occupancy_tracking_desc')],
                            ] as $wireModel => [$label, $desc])
                                <div class="flex items-center justify-between p-3 bg-[#0f172a] border border-slate-700/50 rounded-lg">
                                    <div class="mr-3">
                                        <div class="text-xs font-medium text-white">{{ $label }}</div>
                                        <div class="text-[10px] text-slate-400">{{ $desc }}</div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                        <input type="checkbox" wire:model="{{ $wireModel }}" class="sr-only peer">
                                        <div class="w-10 h-5 bg-slate-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#c8ff00]"></div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex gap-3 p-6 border-t border-slate-700 sticky bottom-0 bg-[#1e293b]">
                    <button wire:click="closeModal"
                        class="flex-1 px-4 py-2.5 bg-transparent border border-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
                        {{ __('platform.common.cancel') }}
                    </button>
                    <button wire:click="savePlan"
                        class="flex-1 px-4 py-2.5 bg-[#c8ff00] text-black rounded-lg hover:bg-[#d4ff33] transition-colors font-semibold">
                        <span wire:loading.remove wire:target="savePlan">{{ $isCreating ? __('platform.common.create_plan') : __('platform.common.update_plan') }}</span>
                        <span wire:loading wire:target="savePlan">{{ __('platform.common.saving') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif


    {{-- ═══════════════════════════════════════════════════════════
    DELETE CONFIRMATION MODAL
    ═══════════════════════════════════════════════════════════ --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
            <div class="bg-[#1e293b] border border-slate-700 rounded-xl w-full max-w-sm p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-red-600/20 border border-red-600/30 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-white font-bold">{{ __('platform.common.delete_plan') }}</h3>
                        <p class="text-slate-400 text-sm">{{ __('platform.plans.delete_undone') }}</p>
                    </div>
                </div>
                <p class="text-slate-300 text-sm mb-6">
                    {{ __('platform.plans.delete_confirm') }} <span class="text-white font-semibold">{{ $planToDeleteName }}</span>?
                    {{ __('platform.plans.delete_note') }}
                </p>
                <div class="flex gap-3">
                    <button wire:click="cancelDelete"
                        class="flex-1 px-4 py-2.5 border border-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
                        {{ __('platform.common.cancel') }}
                    </button>
                    <button wire:click="confirmDelete"
                        class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold">
                        <span wire:loading.remove wire:target="confirmDelete">{{ __('platform.common.delete_perm') }}</span>
                        <span wire:loading wire:target="confirmDelete">{{ __('platform.common.deleting') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
