<div>
    <!-- Page Header -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="text-center lg:text-left">
            <h1 class="text-3xl sm:text-4xl font-bungee text-white uppercase tracking-wider mb-2"
                style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">{{ __('platform.dashboard.owner_overview') }}</h1>
            <p class="text-slate-400 text-sm">{{ __('platform.dashboard.platform_analytics') }}</p>
        </div>
        <div class="flex items-center justify-center lg:justify-end gap-3 sm:gap-4 overflow-x-auto pb-2 sm:pb-0">
            <!-- Period Filter -->
            <div class="relative flex-shrink-0">
                <select wire:model.live="period"
                    class="px-4 py-2 bg-transparent hover:bg-[#1a1144] border border-[#1e164e] rounded text-white text-sm focus:outline-none focus:border-[#c8ff00] cursor-pointer"
                    wire:loading.class="opacity-50 cursor-wait" wire:target="period">
                    <option value="last_7_days">{{ __('platform.dashboard.period.last_7') }}</option>
                    <option value="last_30_days">{{ __('platform.dashboard.period.last_30') }}</option>
                    <option value="this_month">{{ __('platform.dashboard.period.this_month') }}</option>
                    <option value="this_year">{{ __('platform.dashboard.period.this_year') }}</option>
                </select>
            </div>
            <!-- Export Button -->
            <button wire:click="exportData"
                class="flex-shrink-0 px-4 py-2 bg-[#c8ff00] hover:bg-[#d4ff33] text-black font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove
                    wire:target="exportData">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2-2z">
                    </path>
                </svg>
                <span wire:loading.remove wire:target="exportData"
                    class="text-sm font-bold uppercase tracking-wide">{{ __('platform.dashboard.export_data') }}</span>
                <span wire:loading wire:target="exportData"
                    class="text-sm font-bold uppercase tracking-wide">{{ __('platform.common.exporting') }}</span>
            </button>
        </div>
    </div>

    <!-- Stat Cards Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- {{ __('platform.dashboard.total_users') }} -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-[#1a0e40] rounded-xl">
                    <svg class="w-6 h-6 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
                @if($stats['users_change'] != 0)
                    <div class="flex items-center gap-1 text-[10px] px-2 py-1 bg-[#1a0e40] rounded text-slate-400">
                        <span
                            class="font-medium">{{ $stats['users_change'] > 0 ? '+' : '' }}{{ $stats['users_change'] }}%</span>
                    </div>
                @endif
            </div>
            <div class="space-y-1 flex-1">
                <p class="text-[10px] text-slate-300 font-bold uppercase tracking-widest">
                    {{ __('platform.dashboard.total_users') }}
                </p>
                <p class="text-4xl font-bungee text-[#c8ff00]">{{ number_format($stats['total_users']) }}</p>
            </div>
        </div>

        <!-- {{ __('platform.dashboard.total_cafes') }} -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-[#1a0e40] rounded-xl">
                    <svg class="w-6 h-6 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                        </path>
                    </svg>
                </div>
                @if($stats['cafes_change'] != 0)
                    <div class="flex items-center gap-1 text-[10px] px-2 py-1 bg-[#1a0e40] rounded text-slate-400">
                        <span
                            class="font-medium">{{ $stats['cafes_change'] > 0 ? '+' : '' }}{{ $stats['cafes_change'] }}%</span>
                    </div>
                @endif
            </div>
            <div class="space-y-1 flex-1">
                <p class="text-[10px] text-slate-300 font-bold uppercase tracking-widest">
                    {{ __('platform.dashboard.total_cafes') }}
                </p>
                <p class="text-4xl font-bungee text-[#c8ff00]">{{ number_format($stats['total_cafes']) }}</p>
            </div>
        </div>

        <!-- {{ __('platform.dashboard.active_matches') }} -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-[#1a0e40] rounded-xl">
                    <svg class="w-6 h-6 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                        </path>
                    </svg>
                </div>
                @if($stats['active_matches'] > 0)
                    <div class="flex items-center gap-1 text-[10px] px-2 py-1 bg-[#1a0e40] rounded text-[cyan]">
                        <span class="font-medium">Live</span>
                    </div>
                @endif
            </div>
            <div class="space-y-1 flex-1">
                <p class="text-[10px] text-slate-300 font-bold uppercase tracking-widest">
                    {{ __('platform.dashboard.active_matches') }}
                </p>
                <p class="text-4xl font-bungee text-[#c8ff00]">{{ number_format($stats['active_matches']) }}</p>
            </div>
        </div>

        <!-- {{ __('platform.dashboard.monthly_revenue') }} -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-[#1a0e40] rounded-xl">
                    <svg class="w-6 h-6 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
                @if($stats['revenue_change'] != 0)
                    <div class="flex items-center gap-1 text-[10px] px-2 py-1 bg-[#1a0e40] rounded text-slate-400">
                        <span
                            class="font-medium">{{ $stats['revenue_change'] > 0 ? '+' : '' }}{{ $stats['revenue_change'] }}%</span>
                    </div>
                @endif
            </div>
            <div class="space-y-1 flex-1">
                <p class="text-[10px] text-slate-300 font-bold uppercase tracking-widest">
                    {{ __('platform.dashboard.monthly_revenue') }}
                </p>
                <p class="text-4xl font-bungee text-[#c8ff00]">
                    ${{ number_format($stats['monthly_revenue'] / 1000, 1) }}K</p>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Bookings Over Time -->
        <div x-data="{ 
                chart: null,
                labels: {{ json_encode($bookingsChartData['labels']) }},
                values: {{ json_encode($bookingsChartData['values']) }},
                init() {
                    this.$nextTick(() => this.initChart());
                    this.$watch('labels', () => this.initChart());
                    this.$watch('values', () => this.initChart());
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
                        type: 'line',
                        data: {
                            labels: this.labels,
                            datasets: [{
                                label: 'Bookings',
                                data: this.values,
                                borderColor: '#c8ff00',
                                backgroundColor: 'rgba(200, 255, 0, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                pointHoverRadius: 5
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#1e293b',
                                    titleColor: '#e2e8f0',
                                    bodyColor: '#94a3b8',
                                    borderColor: '#334155',
                                    borderWidth: 1
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#334155', drawBorder: false },
                                    ticks: { color: '#94a3b8', font: { size: 10 } }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { color: '#94a3b8', font: { size: 10 } }
                                }
                            }
                        }
                    });
                }
            }" class="bg-[#1e293b] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-white uppercase tracking-wide">
                    {{ __('platform.dashboard.bookings_over_time') }}
                </h3>
                <div class="flex gap-2">
                    <button wire:click="$set('chartPeriod', 'week')"
                        class="px-3 py-1 text-xs font-semibold rounded {{ $chartPeriod === 'week' ? 'bg-[#c8ff00] text-black' : 'text-slate-400 hover:text-white' }}">{{ __('platform.dashboard.week') }}</button>
                    <button wire:click="$set('chartPeriod', 'month')"
                        class="px-3 py-1 text-xs font-semibold rounded {{ $chartPeriod === 'month' ? 'bg-[#c8ff00] text-black' : 'text-slate-400 hover:text-white' }}">{{ __('platform.dashboard.month') }}</button>
                    <button wire:click="$set('chartPeriod', 'year')"
                        class="px-3 py-1 text-xs font-semibold rounded {{ $chartPeriod === 'year' ? 'bg-[#c8ff00] text-black' : 'text-slate-400 hover:text-white' }}">{{ __('platform.dashboard.year') }}</button>
                </div>
            </div>
            <div class="relative h-[250px]" wire:ignore>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <!-- Revenue Growth -->
        <div x-data="{ 
                chart: null,
                labels: {{ json_encode($revenueChartData['labels']) }},
                values: {{ json_encode($revenueChartData['values']) }},
                init() {
                    this.$nextTick(() => this.initChart());
                    this.$watch('labels', () => this.initChart());
                    this.$watch('values', () => this.initChart());
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
                            datasets: [{
                                label: 'Revenue ($)',
                                data: this.values,
                                backgroundColor: '#c8ff00',
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: '#1e293b',
                                    titleColor: '#e2e8f0',
                                    bodyColor: '#94a3b8',
                                    borderColor: '#334155',
                                    borderWidth: 1,
                                    callbacks: {
                                        label: function(context) {
                                            return 'Revenue: $' + context.parsed.y.toLocaleString();
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#334155', drawBorder: false },
                                    ticks: { 
                                        color: '#94a3b8', 
                                        font: { size: 10 },
                                        callback: value => '$' + value.toLocaleString()
                                    }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { color: '#94a3b8', font: { size: 10 } }
                                }
                            }
                        }
                    });
                }
            }" class="bg-[#1e293b] border border-[#1e164e] rounded-xl p-6">
            <div class="mb-6">
                <h3 class="text-lg font-bold text-white uppercase tracking-wide">
                    {{ __('platform.dashboard.revenue_growth') }}
                </h3>
                <p class="text-sm text-slate-400 mt-1">{{ __('platform.dashboard.revenue_analysis') }}</p>
            </div>
            <div class="relative h-[250px]" wire:ignore>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <!-- Bottom Row (3 columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- {{ __('platform.dashboard.top_cafes') }} -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center gap-2 mb-6">
                <div class="p-2 bg-[#1a0e40] rounded-xl">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-sm font-black text-white font-bungee uppercase tracking-wide">
                    {{ __('platform.dashboard.top_cafes') }}
                </h3>
            </div>
            <div class="space-y-4">
                @forelse($topCafes as $index => $cafe)
                    <div class="flex items-center gap-3 bg-[#12082b] p-3 rounded-xl border border-[#1e164e]/50">
                        <div
                            class="w-8 h-8 rounded-lg {{ $index === 0 ? 'bg-[#c8ff00] text-black' : 'bg-[#1a0e40] text-white' }} flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-black font-bungee">{{ $index + 1 }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ $cafe->name }}</p>
                            <p class="text-xs text-slate-400">{{ $cafe->bookings_count }} bookings</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-[#c8ff00]">
                                ${{ number_format(($cafe->branches->sum('revenue') ?? 0) / 1000, 1) }}K</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 text-center py-4">{{ __('platform.dashboard.no_cafes') }}</p>
                @endforelse
            </div>
        </div>

        <!-- {{ __('platform.dashboard.user_stats') }} -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center gap-2 mb-6">
                <div class="p-2 bg-[#1a0e40] rounded-xl">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
                <h3 class="text-sm font-black text-white font-bungee uppercase tracking-wide">
                    {{ __('platform.dashboard.user_stats') }}
                </h3>
            </div>
            <div class="space-y-4">
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-slate-300">{{ __('platform.dashboard.active_users') }}</span>
                        <span
                            class="text-xs font-semibold text-white">{{ number_format($userStats['active_users']) }}</span>
                    </div>
                    <div class="w-full bg-[#1e164e] rounded-full h-1.5">
                        <div class="bg-[#c8ff00] h-1.5 rounded-full"
                            style="width: {{ $userStats['total_users'] > 0 ? ($userStats['active_users'] / $userStats['total_users']) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-slate-300">{{ __('platform.dashboard.premium_users') }}</span>
                        <span
                            class="text-xs font-semibold text-white">{{ number_format($userStats['premium_users']) }}</span>
                    </div>
                    <div class="w-full bg-[#1e164e] rounded-full h-1.5">
                        <div class="bg-[#c8ff00] h-1.5 rounded-full"
                            style="width: {{ $userStats['total_users'] > 0 ? ($userStats['premium_users'] / $userStats['total_users']) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-slate-300">{{ __('platform.dashboard.new_this_month') }}</span>
                        <span
                            class="text-xs font-semibold text-white">{{ number_format($userStats['new_this_month']) }}</span>
                    </div>
                    <div class="w-full bg-[#1e164e] rounded-full h-1.5">
                        <div class="bg-blue-500 h-1.5 rounded-full"
                            style="width: {{ $userStats['total_users'] > 0 ? ($userStats['new_this_month'] / $userStats['total_users']) * 100 : 0 }}%">
                        </div>
                    </div>
                </div>
                <div class="mt-6">
                    <div
                        class="bg-[#140b40] border border-[#261b5c] rounded-lg p-4 flex items-center justify-between relative overflow-hidden">
                        <div class="absolute top-0 right-0 p-3 opacity-20">
                            <svg class="w-12 h-12 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-black font-bungee text-white">
                                {{ $userStats['total_users'] > 0 ? number_format(($userStats['active_users'] / $userStats['total_users']) * 100, 1) : 0 }}%
                            </p>
                            <p class="text-[10px] text-slate-400 uppercase tracking-widest mt-1">
                                {{ __('platform.dashboard.retention_rate') }}
                            </p>
                        </div>
                        <div class="text-[#c8ff00] z-10">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- {{ __('platform.dashboard.recent_activity') }} -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-sm font-black font-bungee text-white uppercase tracking-wide">
                    {{ __('platform.dashboard.recent_activity') }}
                </h3>
                <button wire:click="toggleActivityView"
                    class="text-xs text-[#c8ff00] hover:text-[#d4ff33] transition-colors"
                    wire:loading.class="opacity-50" wire:target="toggleActivityView">
                    <span wire:loading.remove wire:target="toggleActivityView">
                        {{ $showAllActivity ? __('platform.common.show_less') : __('platform.common.view_all') }}
                    </span>
                    <span wire:loading wire:target="toggleActivityView">{{ __('platform.common.loading') }}</span>
                </button>
            </div>
            <div class="space-y-4">
                @forelse($recentActivity as $activity)
                    <div class="flex items-start gap-3">
                        <div class="p-2.5 bg-[#1a0e40] rounded-full flex-shrink-0">
                            @if($activity['icon'] === 'check')
                                <svg class="w-4 h-4 text-{{ $activity['color'] }}-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                    </path>
                                </svg>
                            @elseif($activity['icon'] === 'trophy')
                                <svg class="w-4 h-4 text-{{ $activity['color'] }}-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                                    </path>
                                </svg>
                            @elseif($activity['icon'] === 'star')
                                <svg class="w-4 h-4 text-{{ $activity['color'] }}-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                                    </path>
                                </svg>
                            @else
                                <svg class="w-4 h-4 text-{{ $activity['color'] }}-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                    </path>
                                </svg>
                            @endif
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white">{{ $activity['title'] }}</p>
                            <p class="text-xs text-slate-400 truncate">{{ $activity['subtitle'] }}</p>
                            <p class="text-xs text-slate-500 mt-1">{{ $activity['time'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-400 text-center py-4">{{ __('platform.common.no_activity') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Recent Matches Table -->
    <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden mt-6">
        <div class="p-6 border-b border-[#1e164e] flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <h3 class="text-lg font-black font-bungee text-white mb-0 uppercase tracking-wide">
                {{ __('platform.dashboard.recent_matches') }}
            </h3>
            <div class="flex items-center gap-2 sm:gap-4">
                <div class="relative flex-1 sm:flex-none">
                    <input type="text" wire:model.live="searchMatches"
                        placeholder="{{ __('platform.dashboard.search_matches') }}"
                        class="w-full sm:w-64 px-4 py-2 bg-[#1a0e40] border border-[#1e164e] rounded-lg text-white text-sm placeholder-slate-500 focus:outline-none focus:border-[#c8ff00]">
                </div>
                <button class="p-2 text-slate-400 hover:text-white flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z">
                        </path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-[#12082b]">
                    <tr>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.dashboard.match_id') }}
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.common.cafe') }}
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.dashboard.date_time') }}
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.dashboard.duration') }}
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.dashboard.teams') }}
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.dashboard.revenue') }}
                        </th>
                        <th class="px-6 py-4 text-left text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.common.status') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#1e164e]">
                    @forelse($recentMatches as $match)
                        <tr class="hover:bg-[#1a0e40] transition-colors">
                            <td class="px-6 py-4 text-sm text-white font-mono">
                                #M-{{ str_pad($match->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="px-6 py-4 text-sm text-slate-300">{{ $match->branch->cafe->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-300">
                                {{ $match->match_date ? \Carbon\Carbon::parse($match->match_date)->format('M d, Y') : 'N/A' }}
                                @if($match->kick_off)
                                    {{ is_string($match->kick_off) ? $match->kick_off : $match->kick_off->format('H:i') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-300">{{ $match->homeTeam->name ?? 'TBD' }} vs
                                {{ $match->awayTeam->name ?? 'TBD' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-300">{{ $match->duration_minutes ?? 90 }} min</td>
                            <td class="px-6 py-4 text-sm text-[#c8ff00] font-black font-bungee">
                                ${{ number_format(($match->total_revenue ?? 0) / 100, 2) }}</td>
                            <td class="px-6 py-4">
                                @if($match->status === 'completed')
                                    <span
                                        class="px-2 py-1 bg-green-500/20 text-green-400 text-[10px] font-bold uppercase rounded border border-green-500/50">{{ __('platform.dashboard.completed') }}</span>
                                @elseif($match->status === 'live')
                                    <span
                                        class="px-2 py-1 bg-[#c8ff00]/20 text-[#c8ff00] text-[10px] font-bold uppercase rounded border border-[#c8ff00]/50">{{ __('platform.dashboard.in_progress') }}</span>
                                @else
                                    <span
                                        class="px-2 py-1 bg-blue-500/20 text-blue-400 text-[10px] font-bold uppercase rounded border border-blue-500/50">{{ __('platform.dashboard.scheduled') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                {{ __('platform.dashboard.no_matches') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-[#1e164e]">
            {{ $recentMatches->links() }}
        </div>
    </div>
</div>