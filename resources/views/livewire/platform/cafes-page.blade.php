<div>
    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg flex items-center gap-3">
            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-green-400 font-medium">{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('info'))
        <div class="mb-6 p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-blue-400 font-medium">{{ session('info') }}</span>
        </div>
    @endif

    <!-- Page Header -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="text-center lg:text-left">
            <h1 class="text-3xl sm:text-4xl font-bungee text-white uppercase tracking-wider mb-2"
                style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">{{ __('platform.cafes.title') }}</h1>
            <p class="text-slate-400 text-sm">{{ __('platform.cafes.subtitle') }}</p>
        </div>
    </div>

    <!-- Stat Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Cafes -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-[#c8ff00]/10 rounded-lg">
                    <svg class="w-6 h-6 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
                @if($stats['cafes_change'] != 0)
                    <div
                        class="flex items-center gap-1 text-sm {{ $stats['cafes_change'] > 0 ? 'text-green-500' : 'text-red-500' }}">
                        @if($stats['cafes_change'] > 0)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        @endif
                        <span class="font-semibold">{{ abs($stats['cafes_change']) }}%</span>
                    </div>
                @endif
            </div>
            <div class="space-y-1">
                <p class="text-3xl font-bold text-white">{{ number_format($stats['total_cafes']) }}</p>
                <p class="text-sm text-slate-400 uppercase tracking-wider">{{ __('platform.cafes.total_cafes') }}</p>
            </div>
        </div>

        <!-- Premium Cafes -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-yellow-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z">
                        </path>
                    </svg>
                </div>
            </div>
            <div class="space-y-1">
                <p class="text-3xl font-bold text-white">{{ number_format($stats['premium_cafes']) }}</p>
                <p class="text-sm text-slate-400 uppercase tracking-wider">{{ __('platform.cafes.premium_cafes') }}</p>
            </div>
        </div>

        <!-- Active Bookings -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-blue-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                        </path>
                    </svg>
                </div>
                @if($stats['bookings_change'] != 0)
                    <div
                        class="flex items-center gap-1 text-sm {{ $stats['bookings_change'] > 0 ? 'text-green-500' : 'text-red-500' }}">
                        @if($stats['bookings_change'] > 0)
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                            </svg>
                        @else
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        @endif
                        <span class="font-semibold">{{ abs($stats['bookings_change']) }}%</span>
                    </div>
                @endif
            </div>
            <div class="space-y-1">
                <p class="text-3xl font-bold text-white">{{ number_format($stats['active_bookings']) }}</p>
                <p class="text-sm text-slate-400 uppercase tracking-wider">{{ __('platform.cafes.active_bookings') }}
                </p>
            </div>
        </div>

        <!-- Average Rating -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-orange-500/10 rounded-lg">
                    <svg class="w-6 h-6 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z">
                        </path>
                    </svg>
                </div>
            </div>
            <div class="space-y-1">
                <p class="text-3xl font-bold text-white">{{ number_format($stats['avg_rating'], 1) }}</p>
                <p class="text-sm text-slate-400 uppercase tracking-wider">{{ __('platform.cafes.avg_rating') }}</p>
            </div>
        </div>
    </div>

    <!-- Filters Row -->
    <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- City Filter -->
            <select wire:model.live="cityFilter"
                class="px-4 py-2 bg-[#0a0524] border border-[#1e164e] rounded-lg text-white text-sm focus:outline-none focus:border-[#c8ff00]">
                <option value="">{{ __('platform.cafes.all_cities') }}</option>
                @foreach($cities as $city)
                    <option value="{{ $city }}">{{ $city }}</option>
                @endforeach
            </select>

            <!-- Subscription Filter -->
            <select wire:model.live="subscriptionFilter"
                class="px-4 py-2 bg-[#0a0524] border border-[#1e164e] rounded-lg text-white text-sm focus:outline-none focus:border-[#c8ff00]">
                <option value="">{{ __('platform.cafes.all_subscriptions') }}</option>
                <option value="Starter">Starter</option>
                <option value="Pro">Pro</option>
                <option value="Premium">Premium</option>
                <option value="Enterprise">Enterprise</option>
                <option value="Elite">Elite</option>
            </select>

            <!-- Status Filter -->
            <select wire:model.live="statusFilter"
                class="px-4 py-2 bg-[#0a0524] border border-[#1e164e] rounded-lg text-white text-sm focus:outline-none focus:border-[#c8ff00]">
                <option value="">{{ __('platform.cafes.all_status') }}</option>
                <option value="active">{{ __('platform.cafes.active') }}</option>
                <option value="suspended">{{ __('platform.cafes.suspended') }}</option>
            </select>

            <!-- Search Input -->
            <div class="lg:col-span-2 relative">
                <input type="text" wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('platform.cafes.search') }}"
                    class="w-full px-4 py-2 pl-10 bg-[#0a0524] border border-[#1e164e] rounded-lg text-white text-sm placeholder-slate-500 focus:outline-none focus:border-[#c8ff00]">
                <svg class="w-5 h-5 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
        </div>
    </div>

    <!-- Cafes Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse($cafes as $cafe)
            <div
                class="bg-[#0e0735] border {{ $cafe->trashed() ? 'border-red-500/40' : 'border-[#1e164e] hover:border-[#c8ff00]/50' }} rounded-xl transition-colors group">
                <!-- Cover Image -->
                <div class="relative h-48 bg-gradient-to-br from-slate-700 to-slate-800 overflow-hidden rounded-t-xl">
                    @if($cafe->logo && is_array($cafe->logo) && isset($cafe->logo[0]))
                        <img src="{{ $cafe->logo[0] }}" alt="{{ $cafe->name }}"
                            class="w-full h-full object-cover {{ $cafe->trashed() ? 'opacity-40' : '' }}">
                    @else
                        <div class="w-full h-full flex items-center justify-center">
                            <svg class="w-16 h-16 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                </path>
                            </svg>
                        </div>
                    @endif

                    <!-- Suspended overlay -->
                    @if($cafe->trashed())
                        <div class="absolute inset-0 bg-red-900/30 flex items-center justify-center">
                            <span
                                class="px-4 py-2 bg-red-600 text-white text-sm font-bold uppercase rounded-full tracking-wider">{{ __('platform.cafes.suspended') }}</span>
                        </div>
                    @endif

                    <!-- Subscription Badge -->
                    <div class="absolute top-4 right-4">
                        @php
                            $activeSub = $cafe->subscriptions->first();
                            $planName = $activeSub?->plan?->name ?? 'STANDARD';
                            $badgeColors = [
                                'Premium' => 'bg-[#c8ff00] text-black',
                                'Elite' => 'bg-[#c8ff00] text-black',
                                'Pro' => 'bg-white text-black',
                                'Enterprise' => 'bg-[#c8ff00] text-black',
                                'Starter' => 'bg-white text-black',
                                'Basic' => 'bg-white text-black',
                                'STANDARD' => 'bg-white text-black',
                            ];
                            $badgeClass = $badgeColors[$planName] ?? 'bg-white text-black';
                        @endphp
                        @if(!$cafe->trashed())
                            <span class="px-3 py-1 {{ $badgeClass }} text-xs font-bold uppercase rounded-full">
                                {{ $planName }}
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Card Content -->
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-6">
                        @php
                            $logoColors = ['bg-white text-black', 'bg-[#c8ff00] text-black'];
                            $logoColor = $logoColors[$loop->index % 2];
                        @endphp
                        <div class="w-12 h-12 {{ $logoColor }} rounded-xl flex items-center justify-center flex-shrink-0">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                            </svg>
                        </div>
                        <div>
                            <!-- Cafe Name -->
                            <h3 class="text-lg font-bold {{ $cafe->trashed() ? 'text-slate-400' : 'text-white' }}">
                                {{ $cafe->name }}
                            </h3>

                            <!-- Location -->
                            @php
                                $mainBranch = $cafe->branches->first();
                            @endphp
                            @if($mainBranch)
                                <div class="flex items-center gap-1 text-slate-400 text-xs mt-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span>{{ $mainBranch->city ?? 'N/A' }}{{ $mainBranch->area ? ', ' . $mainBranch->area : '' }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="flex items-center justify-between mb-6 pb-6 border-b border-[#1e164e]">
                        <div>
                            <p class="text-[10px] text-slate-400 mb-1">{{ __('platform.cafes.performance') }}</p>
                            <p class="text-lg font-black font-bungee text-[#c8ff00]">94%</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 mb-1">{{ __('platform.cafes.bookings') }}</p>
                            <p class="text-lg font-black font-bungee text-white">{{ $cafe->active_bookings ?? 0 }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-400 mb-1">{{ __('platform.cafes.rating') }}</p>
                            <p class="text-lg font-black font-bungee text-white">
                                {{ number_format($cafe->avg_rating ?? 0, 1) }} <span class="text-[#c8ff00] text-sm">★</span>
                            </p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-3">
                        <a href="{{ route('platform.cafes.show', $cafe) }}"
                            class="flex-1 px-4 py-2.5 bg-[#c8ff00] hover:bg-[#d4ff33] text-black text-sm font-bold rounded-lg transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            {{ __('platform.cafes.view_details') }}
                        </a>
                        <button wire:click="toggleFeatured({{ $cafe->id }})"
                            class="w-[42px] h-[42px] flex items-center justify-center border border-[#1e164e] bg-[#0a0524] text-slate-400 hover:text-[#c8ff00] hover:bg-[#1a0e40] rounded-lg transition-colors"
                            title="Toggle Featured">
                            <svg class="w-5 h-5 {{ $cafe->is_featured ? 'fill-[#c8ff00] text-[#c8ff00]' : 'fill-none' }}"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                </path>
                            </svg>
                        </button>
                        <!-- Kebab Menu -->
                        <div x-data="{ open: false }" x-on:close-dropdown.window="open = false" class="relative">
                            <button @click="open = !open" @click.away="open = false"
                                class="w-[42px] h-[42px] flex items-center justify-center border border-[#1e164e] bg-[#0a0524] text-slate-400 hover:text-white hover:bg-[#1a0e40] rounded-lg transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="5" r="1.5" />
                                    <circle cx="12" cy="12" r="1.5" />
                                    <circle cx="12" cy="19" r="1.5" />
                                </svg>
                            </button>
                            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                class="absolute right-0 bottom-[calc(100%+0.5rem)] w-48 bg-[#0a0524] border border-[#1e164e] rounded-xl shadow-[0_8px_30px_rgba(0,0,0,0.8)] z-50 py-2">
                                <a href="{{ route('platform.cafes.show', $cafe) }}"
                                    class="flex items-center gap-2.5 px-4 py-2 text-sm text-white hover:bg-[#1a0e40] transition-colors">
                                    <svg class="w-4 h-4 flex-shrink-0 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <span
                                        class="font-medium whitespace-nowrap">{{ __('platform.cafes.view_details') }}</span>
                                </a>
                                <div class="h-px bg-[#1e164e] mx-3 my-1"></div>
                                <button wire:click="toggleCafeStatus({{ $cafe->id }})"
                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-sm text-left transition-colors {{ $cafe->trashed() ? 'text-[#c8ff00] hover:bg-[#1a0e40]' : 'text-orange-400 hover:bg-[#1a0e40]' }}">
                                    @if($cafe->trashed())
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span
                                            class="font-medium whitespace-nowrap">{{ __('platform.cafes.activate_cafe') }}</span>
                                    @else
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span
                                            class="font-medium whitespace-nowrap">{{ __('platform.cafes.suspend_cafe') }}</span>
                                    @endif
                                </button>
                                <div class="h-px bg-[#1e164e] mx-3 my-1"></div>
                                <button wire:click="openDeleteModal({{ $cafe->id }})"
                                    class="flex items-center gap-2.5 w-full px-4 py-2 text-sm text-left text-red-500 hover:bg-[#1a0e40] transition-colors rounded-b-xl">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    <span
                                        class="font-medium whitespace-nowrap">{{ __('platform.cafes.delete_permanently') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 bg-[#0e0735] border border-[#1e164e] rounded-xl p-12 text-center">
                <svg class="w-16 h-16 text-slate-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                    </path>
                </svg>
                <p class="text-slate-400 text-lg">{{ __('platform.cafes.no_cafes') }}</p>
                <p class="text-slate-500 text-sm mt-2">{{ __('platform.cafes.no_cafes_hint') }}</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($cafes->hasPages())
        <div class="flex justify-center">
            {{ $cafes->links() }}
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(0,0,0,0.75);">
            <div class="w-full max-w-md bg-[#0e0735] border border-[#1e164e] rounded-2xl shadow-2xl overflow-hidden">
                <!-- Header -->
                <div class="flex items-center gap-4 p-6 border-b border-[#1e164e]">
                    <div class="p-3 bg-red-500/10 rounded-xl">
                        <svg class="w-6 h-6 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">{{ __('platform.cafes.delete_title') }}</h3>
                        <p class="text-sm text-slate-400">{{ __('platform.cafes.delete_subtitle') }}</p>
                    </div>
                </div>
                <!-- Body -->
                <div class="p-6">
                    <p class="text-slate-300 mb-4">
                        {{ __('platform.cafes.delete_body') }}
                        <span class="font-bold text-white">"{{ $deletingCafeName }}"</span>.
                    </p>
                    <div class="flex items-start gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl">
                        <svg class="w-5 h-5 text-red-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                        <p class="text-red-300 text-sm">{{ __('platform.cafes.delete_warning') }}</p>
                    </div>
                </div>
                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-[#1e164e]">
                    <button wire:click="closeDeleteModal"
                        class="px-5 py-2.5 text-sm font-semibold text-slate-300 bg-slate-700 hover:bg-slate-600 rounded-xl transition-colors">
                        {{ __('platform.common.cancel') }}
                    </button>
                    <button wire:click="confirmDelete" wire:loading.attr="disabled" wire:target="confirmDelete"
                        class="px-5 py-2.5 text-sm font-semibold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 rounded-xl transition-colors flex items-center gap-2">
                        <span wire:loading.remove
                            wire:target="confirmDelete">{{ __('platform.cafes.delete_permanently') }}</span>
                        <span wire:loading wire:target="confirmDelete">{{ __('platform.cafes.deleting') }}</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>