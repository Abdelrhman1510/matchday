<div class="space-y-6">
    <!-- Header -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="text-center lg:text-left">
            <h1 class="text-3xl sm:text-4xl font-black font-bungee text-white uppercase tracking-wider mb-2"
                style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">{{ __('platform.users.title') }}</h1>
            <p class="text-sm text-slate-400">{{ __('platform.users.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-3 overflow-x-auto pb-2 sm:pb-0">
            <div class="flex-shrink-0">
                <select wire:model.live="period"
                    class="px-4 py-2 bg-[#0a0524] border border-[#1e164e] text-slate-300 rounded-lg text-sm focus:outline-none focus:border-[#c8ff00] transition-colors">
                    <option value="30" selected>{{ __('platform.common.last_30_days') }}</option>
                    <option value="60">{{ __('platform.common.last_60_days') }}</option>
                    <option value="90">{{ __('platform.common.last_90_days') }}</option>
                </select>
            </div>
            <button wire:click="exportUsers"
                class="flex-shrink-0 px-5 py-2 bg-[#c8ff00] hover:bg-[#d4ff33] text-black font-bold rounded-lg transition-colors flex items-center gap-2 text-sm"
                wire:loading.attr="disabled" wire:target="exportUsers">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove
                    wire:target="exportUsers">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span wire:loading.remove wire:target="exportUsers">{{ __('platform.users.export') }}</span>
                <span wire:loading wire:target="exportUsers">{{ __('platform.users.exporting') }}</span>
            </button>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Active Users -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                        </path>
                    </svg>
                </div>
                <div class="flex items-center gap-1 text-[10px] text-[#c8ff00] font-medium">
                    @if($stats['active_users_change'] > 0)
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        +{{ $stats['active_users_change'] }}%
                    @elseif($stats['active_users_change'] < 0)
                        <svg class="w-3 h-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                        <span class="text-red-400">{{ $stats['active_users_change'] }}%</span>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">
                    {{ __('platform.users.active_users') }}
                </p>
                <p class="text-[36px] font-black font-bungee text-white leading-none">
                    {{ number_format($stats['active_users']) }}
                </p>
            </div>
            <!-- Mini bar chart -->
            <div class="flex items-end gap-[3px] h-10 mt-4">
                @php $heights = [35, 50, 40, 60, 45, 70, 55, 80, 65, 90, 75, 100]; @endphp
                @foreach($heights as $h)
                    <div class="flex-1 rounded-sm {{ $loop->last ? 'bg-[#c8ff00]' : 'bg-[#1a0e40]' }}"
                        style="height: {{ $h }}%;"></div>
                @endforeach
            </div>
        </div>

        <!-- VIP Users -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z">
                        </path>
                    </svg>
                </div>
                <div class="flex items-center gap-1 text-[10px] text-[#c8ff00] font-medium">
                    @if($stats['vip_users_change'] > 0)
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        +{{ $stats['vip_users_change'] }}%
                    @elseif($stats['vip_users_change'] < 0)
                        <span class="text-red-400">{{ $stats['vip_users_change'] }}%</span>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">
                    {{ __('platform.users.vip_users') }}
                </p>
                <p class="text-[36px] font-black font-bungee text-white leading-none">
                    {{ number_format($stats['vip_users']) }}
                </p>
            </div>
            <!-- Mini bar chart -->
            <div class="flex items-end gap-[3px] h-10 mt-4">
                @php $heights2 = [25, 45, 30, 55, 40, 65, 50, 75, 60, 85, 70, 95]; @endphp
                @foreach($heights2 as $h)
                    <div class="flex-1 rounded-sm {{ $loop->last ? 'bg-[#c8ff00]' : 'bg-[#1a0e40]' }}"
                        style="height: {{ $h }}%;"></div>
                @endforeach
            </div>
        </div>

        <!-- New Signups -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-4">
                <div class="w-10 h-10 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z">
                        </path>
                    </svg>
                </div>
                <div class="flex items-center gap-1 text-[10px] text-[#c8ff00] font-medium">
                    @if($stats['new_signups_change'] > 0)
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        +{{ $stats['new_signups_change'] }}%
                    @elseif($stats['new_signups_change'] < 0)
                        <span class="text-red-400">{{ $stats['new_signups_change'] }}%</span>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-1">
                    {{ __('platform.users.new_signups') }}
                </p>
                <p class="text-[36px] font-black font-bungee text-white leading-none">
                    {{ number_format($stats['new_signups']) }}
                </p>
            </div>
            <!-- Mini bar chart -->
            <div class="flex items-end gap-[3px] h-10 mt-4">
                @php $heights3 = [40, 55, 45, 65, 50, 72, 58, 82, 68, 88, 78, 100]; @endphp
                @foreach($heights3 as $h)
                    <div class="flex-1 rounded-sm {{ $loop->last ? 'bg-[#c8ff00]' : 'bg-[#1a0e40]' }}"
                        style="height: {{ $h }}%;"></div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- User Growth -->
        <div x-data="{
            chart: null,
            labels: {{ json_encode($userGrowth['labels']) }},
            data: {{ json_encode($userGrowth['data']) }},
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
                    type: 'line',
                    data: {
                        labels: this.labels,
                        datasets: [{
                            label: 'Active Users',
                            data: this.data,
                            borderColor: '#c8ff00',
                            backgroundColor: 'rgba(200, 255, 0, 0.07)',
                            borderWidth: 2.5,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#c8ff00',
                            pointBorderColor: '#0e0735',
                            pointBorderWidth: 2,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(30,22,78,0.8)', drawBorder: false }, ticks: { color: '#64748b', font: { size: 10 } } },
                            x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 10 } } }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: '#0a0524', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: '#1e164e', borderWidth: 1 }
                        }
                    }
                });
            }
        }" class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base font-black font-bungee text-white uppercase tracking-wider">
                        {{ __('platform.users.user_growth') }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">{{ __('platform.users.monthly_trend') }}</p>
                </div>
                <div class="w-9 h-9 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
            </div>
            <div wire:ignore class="relative" style="height: 280px;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <!-- Booking Behavior -->
        <div x-data="{
            chart: null,
            labels: {{ json_encode($bookingBehavior['labels']) }},
            data: {{ json_encode($bookingBehavior['data']) }},
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
                        datasets: [{ label: 'Bookings', data: this.data, backgroundColor: '#c8ff00', borderColor: '#c8ff00', borderWidth: 0, borderRadius: 6, borderSkipped: false }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: { beginAtZero: true, grid: { color: 'rgba(30,22,78,0.8)', drawBorder: false }, ticks: { color: '#64748b', font: { size: 10 } } },
                            x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 10 } } }
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: '#0a0524', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: '#1e164e', borderWidth: 1 }
                        }
                    }
                });
            }
        }" class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base font-black font-bungee text-white uppercase tracking-wider">
                        {{ __('platform.users.booking_behavior') }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">{{ __('platform.users.peak_times') }}</p>
                </div>
                <div class="w-9 h-9 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                        </path>
                    </svg>
                </div>
            </div>
            <div wire:ignore class="relative" style="height: 280px;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <!-- Fan Segments -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Casual Fans -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden">
            <!-- Header image area -->
            <div class="relative h-52 overflow-hidden"
                style="background: linear-gradient(135deg, #1a0e40 0%, #0c0628 100%);">
                <div class="absolute inset-0 opacity-40"
                    style="background-image: url('https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=800&auto=format&fit=crop&q=60'); background-size: cover; background-position: center;">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-[#0e0735] via-[#0e0735]/30 to-transparent"></div>
                <!-- Kebab menu icon top right -->
                <div class="absolute top-4 right-4">
                    <div
                        class="w-8 h-8 bg-[#1a0e40]/60 backdrop-blur border border-[#1e164e] rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="5" r="1.5" />
                            <circle cx="12" cy="12" r="1.5" />
                            <circle cx="12" cy="19" r="1.5" />
                        </svg>
                    </div>
                </div>
                <!-- Title at bottom of image -->
                <div class="absolute bottom-4 left-5">
                    <h3 class="text-2xl font-black font-bungee text-white uppercase tracking-wider"
                        style="text-shadow: 1px 1px 4px rgba(0,0,0,0.8);">{{ __('platform.users.casual_fans') }}</h3>
                </div>
            </div>

            <!-- Stats Body -->
            <div class="p-5 space-y-3">
                <!-- Top row: Total Users | Avg Bookings -->
                <div class="flex items-center justify-between mb-1">
                    <div>
                        <p class="text-[10px] text-slate-400 mb-0.5">{{ __('platform.users.total_users') }}</p>
                        <p class="text-2xl font-black font-bungee text-white">
                            {{ number_format($fanSegments['casual']['total_users']) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 mb-0.5">{{ __('platform.users.avg_bookings') }}</p>
                        <p class="text-2xl font-black font-bungee text-white">
                            {{ $fanSegments['casual']['avg_bookings'] }}
                        </p>
                    </div>
                </div>

                <!-- Engagement -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs text-slate-400">{{ __('platform.users.engagement') }}</span>
                        <span
                            class="text-xs font-bold text-[#c8ff00]">{{ $fanSegments['casual']['engagement'] }}%</span>
                    </div>
                    <div class="w-full bg-[#1a0e40] rounded-full h-1.5">
                        <div class="bg-[#c8ff00] h-1.5 rounded-full transition-all duration-700"
                            style="width: {{ $fanSegments['casual']['engagement'] }}%"></div>
                    </div>
                </div>

                <!-- Retention -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs text-slate-400">{{ __('platform.users.retention') }}</span>
                        <span class="text-xs font-bold text-[#c8ff00]">{{ $fanSegments['casual']['retention'] }}%</span>
                    </div>
                    <div class="w-full bg-[#1a0e40] rounded-full h-1.5">
                        <div class="bg-[#c8ff00] h-1.5 rounded-full transition-all duration-700"
                            style="width: {{ $fanSegments['casual']['retention'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIP Fans -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden">
            <!-- Header image area -->
            <div class="relative h-52 overflow-hidden"
                style="background: linear-gradient(135deg, #1a0e40 0%, #0c0628 100%);">
                <div class="absolute inset-0 opacity-40"
                    style="background-image: url('https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=800&auto=format&fit=crop&q=60'); background-size: cover; background-position: center;">
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-[#0e0735] via-[#0e0735]/30 to-transparent"></div>
                <!-- Kebab menu icon top right -->
                <div class="absolute top-4 right-4">
                    <div
                        class="w-8 h-8 bg-[#1a0e40]/60 backdrop-blur border border-[#1e164e] rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="5" r="1.5" />
                            <circle cx="12" cy="12" r="1.5" />
                            <circle cx="12" cy="19" r="1.5" />
                        </svg>
                    </div>
                </div>
                <!-- Title at bottom of image -->
                <div class="absolute bottom-4 left-5">
                    <h3 class="text-2xl font-black font-bungee text-white uppercase tracking-wider"
                        style="text-shadow: 1px 1px 4px rgba(0,0,0,0.8);">{{ __('platform.users.vip_fans') }}</h3>
                </div>
            </div>

            <!-- Stats Body -->
            <div class="p-5 space-y-3">
                <!-- Top row: Total Users | Avg Bookings -->
                <div class="flex items-center justify-between mb-1">
                    <div>
                        <p class="text-[10px] text-slate-400 mb-0.5">{{ __('platform.users.total_users') }}</p>
                        <p class="text-2xl font-black font-bungee text-white">
                            {{ number_format($fanSegments['vip']['total_users']) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] text-slate-400 mb-0.5">{{ __('platform.users.avg_bookings') }}</p>
                        <p class="text-2xl font-black font-bungee text-white">{{ $fanSegments['vip']['avg_bookings'] }}
                        </p>
                    </div>
                </div>

                <!-- Engagement -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs text-slate-400">{{ __('platform.users.engagement') }}</span>
                        <span class="text-xs font-bold text-[#c8ff00]">{{ $fanSegments['vip']['engagement'] }}%</span>
                    </div>
                    <div class="w-full bg-[#1a0e40] rounded-full h-1.5">
                        <div class="bg-[#c8ff00] h-1.5 rounded-full transition-all duration-700"
                            style="width: {{ $fanSegments['vip']['engagement'] }}%"></div>
                    </div>
                </div>

                <!-- Retention -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <span class="text-xs text-slate-400">{{ __('platform.users.retention') }}</span>
                        <span class="text-xs font-bold text-[#c8ff00]">{{ $fanSegments['vip']['retention'] }}%</span>
                    </div>
                    <div class="w-full bg-[#1a0e40] rounded-full h-1.5">
                        <div class="bg-[#c8ff00] h-1.5 rounded-full transition-all duration-700"
                            style="width: {{ $fanSegments['vip']['retention'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>