<div class="space-y-6">
    <!-- Header -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="text-center lg:text-left">
            <h1 class="text-3xl sm:text-4xl font-black font-bungee text-white uppercase tracking-wider mb-2"
                style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">
                {{ __('platform.matches.title') ?? 'MATCHES ANALYTICS' }}
            </h1>
            <p class="text-slate-400 text-sm">
                {{ __('platform.matches.subtitle') ?? 'Match performance and booking trends' }}
            </p>
        </div>
        <div class="flex items-center gap-3 sm:gap-4 overflow-x-auto pb-2 sm:pb-0">
            <div class="flex-shrink-0">
                <select wire:model.live="period"
                    class="px-4 py-2 bg-[#0a0524] border border-[#1e164e] text-slate-300 rounded-lg text-sm focus:outline-none focus:border-[#c8ff00] transition-colors">
                    <option value="30" selected>{{ __('platform.matches.last_30_days') }}</option>
                    <option value="60">{{ __('platform.matches.last_60_days') }}</option>
                    <option value="90">{{ __('platform.matches.last_90_days') }}</option>
                </select>
            </div>
            <button
                class="flex-shrink-0 w-[42px] h-[42px] flex items-center justify-center border border-[#1e164e] bg-[#0a0524] text-slate-400 hover:text-white hover:bg-[#1a0e40] rounded-lg transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9">
                    </path>
                </svg>
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Total Matches Watched -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-8">
                <div class="w-10 h-10 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                        </path>
                    </svg>
                </div>
                <div class="flex items-center gap-1 text-xs text-slate-400 mt-1">
                    @if($stats['matches_change'] > 0)
                        <span class="font-medium">+{{ $stats['matches_change'] }}%</span>
                    @elseif($stats['matches_change'] < 0)
                        <span class="font-medium">{{ $stats['matches_change'] }}%</span>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-[32px] font-black font-bungee text-[#c8ff00] mb-1 leading-none drop-shadow-md">
                    {{ number_format($stats['total_matches']) }}
                </p>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-2">
                    {{ __('platform.matches.total_watched') }}
                </p>
            </div>
        </div>

        <!-- Prime Booking Time -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-8">
                <div class="w-10 h-10 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex items-center gap-1 text-[10px] text-slate-400 mt-1">
                    <span class="font-medium">{{ __('platform.matches.peak') }}</span>
                </div>
            </div>
            <div>
                <p class="text-[32px] font-black font-bungee text-[#c8ff00] mb-1 leading-none drop-shadow-md">
                    {{ $stats['prime_time'] }}
                </p>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-2">
                    {{ __('platform.matches.prime_booking_time') }}
                </p>
            </div>
        </div>

        <!-- Capacity Rate -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-8">
                <div class="w-10 h-10 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="flex items-center gap-1 text-[10px] text-slate-400 mt-1">
                    <span class="font-medium">{{ __('platform.matches.top') }}</span>
                </div>
            </div>
            <div>
                <p class="text-[32px] font-black font-bungee text-[#c8ff00] mb-1 leading-none drop-shadow-md">
                    {{ $stats['capacity_rate'] }}%
                </p>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-2">
                    {{ __('platform.matches.capacity_rate') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Most Watched Matches -->
    <div class="mt-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-black font-bungee text-white uppercase tracking-wider"
                style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">{{ __('platform.matches.most_watched') }}</h2>
            <a href="#" class="text-[#c8ff00] text-sm font-bold hover:underline flex items-center gap-1">
                {{ __('platform.matches.view_all') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3">
                    </path>
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @forelse($mostWatchedMatches as $match)
                <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden">
                    <!-- Match Image -->
                    @php
                        $matchPhotos = [
                            'https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=600&auto=format&fit=crop&q=60',
                            'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=600&auto=format&fit=crop&q=60',
                            'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=600&auto=format&fit=crop&q=60',
                            'https://images.unsplash.com/photo-1551958219-acbc15d09ab1?w=600&auto=format&fit=crop&q=60',
                            'https://images.unsplash.com/photo-1543326727-cf6c39e8f84c?w=600&auto=format&fit=crop&q=60',
                        ];
                        $photo = $matchPhotos[$loop->index % count($matchPhotos)];
                    @endphp
                    <div class="relative h-48 overflow-hidden">
                        <div class="absolute inset-0 bg-cover bg-center transition-transform duration-500 hover:scale-105"
                            style="background-image: url('{{ $photo }}')"></div>
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0e0735] via-[#0e0735]/20 to-transparent"></div>
                        <!-- Status Badge -->
                        <div class="absolute top-4 left-4">
                            @if($match->status === 'live')
                                <span
                                    class="px-3 py-1 bg-[#c8ff00] text-black text-[10px] font-black font-bungee uppercase rounded-full">{{ __('platform.matches.live') }}</span>
                            @elseif($match->status === 'upcoming')
                                <span
                                    class="px-3 py-1 bg-white/20 backdrop-blur-md text-white border border-white/50 text-[10px] font-black font-bungee uppercase rounded-full">{{ __('platform.matches.upcoming') }}</span>
                            @else
                                <span
                                    class="px-3 py-1 bg-white/20 backdrop-blur-md text-white border border-white/50 text-[10px] font-black font-bungee uppercase rounded-full">{{ __('platform.matches.scheduled') }}</span>
                            @endif
                        </div>

                        <!-- Inner Gradient for Text Readability -->
                        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-[#0e0735] to-transparent"></div>

                        <!-- Match Title and Stats inside image overlay to match card proportion if desired -->
                        <div class="absolute bottom-4 left-4 right-4">
                            <h3 class="text-white font-bold text-sm mb-1 line-clamp-1">{{ $match->homeTeam->name ?? 'TBD' }}
                                vs {{ $match->awayTeam->name ?? 'TBD' }}</h3>
                            <div class="flex items-center gap-4 text-[10px] text-[#c8ff00]">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                    <span class="text-white">{{ number_format($match->views) }}</span>
                                </span>
                                <span class="flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-white">{{ $match->total_bookings }}</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Match Details -->
                    <div class="p-4 pt-2 pb-5">
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-[10px] text-slate-400 mb-0.5">{{ __('platform.matches.booking_rate') }}</p>
                                <p class="text-[28px] font-black font-bungee text-[#c8ff00] leading-none">
                                    {{ $match->booking_rate }}%
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[10px] text-slate-400 mb-0.5">{{ __('platform.matches.revenue') }}</p>
                                <p class="text-sm font-bold text-white leading-none mb-[2px]">
                                    ${{ number_format($match->revenue, 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 bg-[#0e0735] border border-[#1e164e] rounded-xl p-8 text-center">
                    <p class="text-slate-400">{{ __('platform.matches.no_data') }}</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Peak Booking Times -->
        <div x-data="{
            chart: null,
            labels: {{ json_encode($peakBookingTimes['labels']) }},
            data: {{ json_encode($peakBookingTimes['data']) }},
            init() {
                this.$nextTick(() => this.initChart());
                this.$watch('labels', () => this.initChart());
                this.$watch('data', () => this.initChart());
            },
            initChart() {
                if (typeof window.Chart === 'undefined') {
                    setTimeout(() => this.initChart(), 100);
                    return;
                }
                const canvas = this.$refs.canvas;
                const existing = window.Chart.getChart(canvas);
                if (existing) existing.destroy();
                
                this.chart = new window.Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: this.labels,
                        datasets: [{ label: 'Bookings', data: this.data, backgroundColor: '#c8ff00', borderColor: '#c8ff00', borderWidth: 1, borderRadius: 8 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#334155', drawBorder: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
                            x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: '#1e293b', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: '#334155', borderWidth: 1 }
                        }
                    }
                });
            }
        }" class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base font-black font-bungee text-white uppercase tracking-wider">
                        {{ __('platform.matches.peak_times') }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">{{ __('platform.matches.hourly_distribution') }}</p>
                </div>
                <div class="w-9 h-9 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div wire:ignore class="relative" style="height: 300px;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <!-- Match to Cafe Performance -->
        <div x-data="{
            chart: null,
            labels: {{ json_encode($matchToCafePerformance['labels']) }},
            datasets: {{ json_encode($matchToCafePerformance['datasets']) }},
            init() {
                this.$nextTick(() => this.initChart());
                this.$watch('labels', () => this.initChart());
                this.$watch('datasets', () => this.initChart());
            },
            initChart() {
                if (typeof window.Chart === 'undefined') {
                    setTimeout(() => this.initChart(), 100);
                    return;
                }
                const canvas = this.$refs.canvas;
                const existing = window.Chart.getChart(canvas);
                if (existing) existing.destroy();
                
                this.chart = new window.Chart(canvas, {
                    type: 'bar',
                    data: { labels: this.labels, datasets: this.datasets },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#334155', drawBorder: false }, ticks: { color: '#94a3b8', font: { size: 11 }, callback: value => '$' + value.toLocaleString() } },
                            x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } }
                        },
                        plugins: {
                            legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 15, font: { size: 12 }, usePointStyle: true, pointStyle: 'circle' } },
                            tooltip: {
                                backgroundColor: '#1e293b', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: '#334155', borderWidth: 1,
                                callbacks: { label: context => context.dataset.label + ': $' + context.parsed.y.toLocaleString() }
                            }
                        }
                    }
                });
            }
        }" class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base font-black font-bungee text-white uppercase tracking-wider">
                        {{ __('platform.matches.cafe_performance') }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">{{ __('platform.matches.top_by_type') }}</p>
                </div>
                <div class="w-9 h-9 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
            </div>
            <div wire:ignore class="relative" style="height: 300px;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <!-- Match Categories -->
    <div class="mt-2">
        <h2 class="text-xl font-black font-bungee text-white uppercase tracking-wider mb-6"
            style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">{{ __('platform.matches.categories') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($matchCategories as $index => $category)
                <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
                    <div class="flex items-start justify-between mb-6">
                        <div
                            class="w-10 h-10 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                            @if($index === 0)
                                <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                                    </path>
                                </svg>
                            @elseif($index === 1)
                                <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                    </path>
                                </svg>
                            @elseif($index === 2)
                                <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H8l-1-1H5a2 2 0 00-2 2zm9-13.5V9">
                                    </path>
                                </svg>
                            @endif
                        </div>
                        @if($category['change'] != 0)
                            <div class="text-[10px] px-2 py-1 rounded bg-[#1a0e40] text-slate-400 font-medium">
                                {{ $category['change'] > 0 ? '+' : '' }}{{ $category['change'] }}%
                            </div>
                        @endif
                    </div>
                    <div>
                        <p class="text-[32px] font-black font-bungee text-[#c8ff00] leading-none mb-2 drop-shadow-md">
                            {{ number_format($category['count']) }}
                        </p>
                        <p class="text-xs text-slate-400 uppercase tracking-widest">{{ $category['name'] }}</p>
                        <p class="text-[10px] text-slate-500 mt-1">{{ __('platform.matches.matches_label') }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>