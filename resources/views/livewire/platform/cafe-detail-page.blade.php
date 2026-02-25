<div class="space-y-6">
    <!-- Flash Messages -->
    @if (session()->has('message'))
        <div class="p-4 bg-green-500/10 border border-green-500/20 rounded-lg flex items-center gap-3">
            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-green-400 font-medium text-sm">{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('info'))
        <div class="p-4 bg-blue-500/10 border border-blue-500/20 rounded-lg flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-blue-400 font-medium text-sm">{{ session('info') }}</span>
        </div>
    @endif

    <!-- Hero Section -->
    <div class="relative rounded-xl overflow-hidden">
        <!-- Banner with real sports bar photo -->
        <div class="relative h-52 overflow-hidden rounded-t-xl">
            <div class="absolute inset-0 bg-cover bg-center"
                style="background-image: url('https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=1200&auto=format&fit=crop&q=80');">
            </div>
            <div class="absolute inset-0 bg-gradient-to-b from-black/30 via-transparent to-[#0e0735]/90"></div>
            <!-- Back button -->
            <a href="{{ url()->previous() }}"
                class="absolute top-4 left-4 flex items-center gap-2 px-3 py-1.5 bg-black/40 backdrop-blur-sm text-white text-xs font-semibold rounded-lg border border-white/20 hover:bg-black/60 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Back
            </a>
        </div>

        <!-- Cafe Info Bar -->
        <div class="bg-[#0e0735] border border-[#1e164e] border-t-0 rounded-b-xl px-4 sm:px-6 py-5">
            <div class="flex flex-col lg:flex-row lg:items-end gap-4 sm:gap-5">
                <!-- Logo -->
                <div
                    class="w-20 h-20 rounded-xl border-2 border-[#1e164e] bg-[#1a0e40] flex items-center justify-center flex-shrink-0 -mt-12 sm:-mt-10 relative z-10 overflow-hidden shadow-xl mx-auto lg:mx-0">
                    @if($cafe->logo && is_array($cafe->logo) && isset($cafe->logo[0]))
                        <img src="{{ $cafe->logo[0] }}" alt="{{ $cafe->name }}" class="w-full h-full object-cover">
                    @else
                        <svg class="w-10 h-10 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                            </path>
                        </svg>
                    @endif
                </div>

                <!-- Name + meta -->
                <div class="flex-1 min-w-0 text-center lg:text-left">
                    <div class="flex items-center justify-center lg:justify-start gap-3 flex-wrap">
                        <h1
                            class="text-xl sm:text-2xl font-black font-bungee text-white uppercase tracking-wide truncate max-w-full">
                            {{ $cafe->name }}
                        </h1>
                        <span
                            class="px-2.5 py-1 bg-[#c8ff00] text-black text-[10px] font-black font-bungee rounded uppercase">VERIFIED</span>
                    </div>
                    @php $mainBranch = $cafe->branches->first(); @endphp
                    @if($mainBranch)
                        <div
                            class="flex items-center justify-center sm:justify-start gap-4 text-slate-400 text-[10px] sm:text-xs mt-1.5 flex-wrap">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                    </path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                {{ $mainBranch->area ?? 'N/A' }}, {{ $mainBranch->city ?? 'N/A' }}
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 text-orange-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z">
                                    </path>
                                </svg>
                                <span class="text-white font-semibold">{{ number_format($cafe->avg_rating ?? 0, 1) }}</span>
                                <span>({{ number_format($cafe->total_reviews ?? 0) }} reviews)</span>
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-center sm:justify-start gap-2 flex-shrink-0">
                    <button wire:click="exportToPDF"
                        class="flex items-center gap-1.5 px-4 py-2 bg-[#1a0e40] border border-[#1e164e] hover:bg-[#1e164e] text-slate-300 text-xs font-bold rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportToPDF">PDF Details</span>
                        <span wire:loading wire:target="exportToPDF">Loading...</span>
                    </button>
                    @if($cafe->website_url)
                        <a href="{{ $cafe->website_url }}" target="_blank" rel="noopener noreferrer"
                            class="flex items-center gap-1.5 px-4 py-2 bg-[#c8ff00] hover:bg-[#d4ff33] text-black text-xs font-bold rounded-lg transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                            </svg>
                            View Public Page
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Overview -->
    <div>
        <h2 class="text-base font-black font-bungee text-white uppercase tracking-wider mb-4">PERFORMANCE OVERVIEW</h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Bookings -->
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <div
                        class="w-8 h-8 bg-[#1a0e40] border border-[#1e164e] rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest">Total Bookings</p>
                </div>
                <p class="text-3xl font-black font-bungee text-white">{{ number_format($performanceStats['bookings']) }}
                </p>
                @if($performanceStats['bookings_change'] != 0)
                    <p
                        class="text-xs mt-1 {{ $performanceStats['bookings_change'] > 0 ? 'text-[#c8ff00]' : 'text-red-400' }}">
                        {{ $performanceStats['bookings_change'] > 0 ? '▲' : '▼' }}
                        {{ abs($performanceStats['bookings_change']) }}% vs last month
                    </p>
                @else
                    <p class="text-[10px] text-slate-500 mt-1">This month</p>
                @endif
            </div>

            <!-- Revenue -->
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <div
                        class="w-8 h-8 bg-[#1a0e40] border border-[#1e164e] rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                            </path>
                        </svg>
                    </div>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest">Revenue</p>
                </div>
                <p class="text-3xl font-black font-bungee text-white">
                    £{{ number_format($performanceStats['revenue'] / 100, 1) }}K</p>
                @if($performanceStats['revenue_change'] != 0)
                    <p
                        class="text-xs mt-1 {{ $performanceStats['revenue_change'] > 0 ? 'text-[#c8ff00]' : 'text-red-400' }}">
                        {{ $performanceStats['revenue_change'] > 0 ? '▲' : '▼' }}
                        {{ abs($performanceStats['revenue_change']) }}% this month
                    </p>
                @else
                    <p class="text-[10px] text-slate-500 mt-1">This month</p>
                @endif
            </div>

            <!-- Occupancy Rate -->
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <div
                        class="w-8 h-8 bg-[#1a0e40] border border-[#1e164e] rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                            </path>
                        </svg>
                    </div>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest">Average</p>
                </div>
                <p class="text-3xl font-black font-bungee text-white">
                    {{ number_format($performanceStats['occupancy_rate'], 1) }}%
                </p>
                <p class="text-[10px] text-slate-500 mt-1">Occupancy Rate</p>
            </div>

            <!-- Customer Rating -->
            <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-5">
                <div class="flex items-center gap-2 mb-3">
                    <div
                        class="w-8 h-8 bg-[#1a0e40] border border-[#1e164e] rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#c8ff00]" fill="currentColor" viewBox="0 0 24 24">
                            <path
                                d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z">
                            </path>
                        </svg>
                    </div>
                    <p class="text-[10px] text-slate-400 uppercase tracking-widest">Average</p>
                </div>
                <p class="text-3xl font-black font-bungee text-white">
                    {{ number_format($performanceStats['rating'], 1) }}
                </p>
                <p class="text-[10px] text-slate-500 mt-1">Customer Rating</p>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Bookings Trend -->
        <div x-data="{
            chart: null,
            labels: {{ json_encode($bookingsChartData['labels']) }},
            data: {{ json_encode($bookingsChartData['values']) }},
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
                        datasets: [{ label: 'Bookings', data: this.data, borderColor: '#c8ff00', backgroundColor: 'rgba(200,255,0,0.08)', borderWidth: 2, fill: true, tension: 0.4, pointRadius: 0, pointHoverRadius: 4 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { backgroundColor: '#0e0735', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: '#1e164e', borderWidth: 1 }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#1e164e' }, ticks: { color: '#94a3b8' } },
                            x: { grid: { color: '#1e164e' }, ticks: { color: '#94a3b8' } }
                        }
                    }
                });
            }
        }" class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-black font-bungee text-white uppercase tracking-wider">BOOKINGS TREND</h3>
                    <p class="text-xs text-slate-400 mt-1">Last 30 days</p>
                </div>
                <div class="w-8 h-8 bg-[#1a0e40] border border-[#1e164e] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path>
                    </svg>
                </div>
            </div>
            <div wire:ignore class="relative" style="height: 250px;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <!-- Revenue by Match Type -->
        <div x-data="{
            chart: null,
            labels: {{ json_encode($revenueByMatchType['labels']) }},
            data: {{ json_encode($revenueByMatchType['values']) }},
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
                    type: 'doughnut',
                    data: {
                        labels: this.labels,
                        datasets: [{ data: this.data, backgroundColor: ['#c8ff00', '#3b82f6', '#f59e0b', '#10b981', '#8b5cf6'], borderColor: '#0e0735', borderWidth: 3 }]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 15, font: { size: 12 } } },
                            tooltip: {
                                backgroundColor: '#0e0735', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: '#1e164e', borderWidth: 1,
                                callbacks: { label: function (c) { return c.label + ': £' + c.parsed.toFixed(2); } }
                            }
                        }
                    }
                });
            }
        }" class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h3 class="text-sm font-black font-bungee text-white uppercase tracking-wider">REVENUE BY MATCH TYPE
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">Revenue distribution</p>
                </div>
                <div class="w-8 h-8 bg-[#1a0e40] border border-[#1e164e] rounded-lg flex items-center justify-center">
                    <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                    </svg>
                </div>
            </div>
            <div wire:ignore class="relative" style="height: 250px;">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <!-- Branches Section -->
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-black font-bungee text-white uppercase tracking-wider">BRANCHES</h2>
            <button
                class="flex items-center gap-1.5 px-4 py-2 bg-[#c8ff00] hover:bg-[#d4ff33] text-black text-xs font-bold rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Add Branch
            </button>
        </div>

        @php
            $branchPhotos = [
                'https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=600&auto=format&fit=crop&q=60',
                'https://images.unsplash.com/photo-1551958219-acbc15d09ab1?w=600&auto=format&fit=crop&q=60',
                'https://images.unsplash.com/photo-1543326727-cf6c39e8f84c?w=600&auto=format&fit=crop&q=60',
            ];
        @endphp
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @forelse($branches as $branch)
                @php $bPhoto = $branchPhotos[$loop->index % count($branchPhotos)]; @endphp
                <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden">
                    <div class="relative h-36 overflow-hidden">
                        <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ $bPhoto }}');">
                        </div>
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0e0735]/80 to-black/20"></div>
                        @if($loop->first)
                            <div class="absolute top-3 left-3">
                                <span
                                    class="px-2.5 py-1 bg-[#c8ff00] text-black text-[10px] font-black font-bungee rounded uppercase">MAIN</span>
                            </div>
                        @endif
                        <div class="absolute bottom-3 left-3 right-3">
                            <h3 class="text-white font-bold text-sm truncate">{{ $branch->name }}</h3>
                        </div>
                    </div>
                    <div class="p-4">
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-0.5">Occupancy</p>
                                <p class="text-2xl font-black font-bungee text-[#c8ff00]">
                                    {{ number_format($branch->occupancy_rate, 0) }}%
                                </p>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-0.5">Capacity</p>
                                <p class="text-2xl font-black font-bungee text-white">{{ $branch->total_seats ?? 0 }}</p>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-400 truncate">{{ $branch->address }}</p>
                    </div>
                </div>
            @empty
                <div class="col-span-3 bg-[#0e0735] border border-[#1e164e] rounded-xl p-8 text-center">
                    <p class="text-slate-400 text-sm">No branches found</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Upcoming Matches Section -->
    <div>
        <h2 class="text-base font-black font-bungee text-white uppercase tracking-wider mb-4">UPCOMING MATCHES</h2>
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl overflow-hidden">
            <div class="divide-y divide-[#1e164e]">
                @forelse($upcomingMatches as $matchData)
                    @php
                        $match = $matchData['match'];
                        $leagueName = $match->league ?? 'Match';
                        $leagueColors = [
                            'Champions League' => 'bg-blue-950/60 border-blue-700/40 text-blue-300',
                            'Premier League' => 'bg-purple-950/60 border-purple-700/40 text-purple-300',
                            'La Liga' => 'bg-orange-950/60 border-orange-700/40 text-orange-300',
                        ];
                        $badgeClass = $leagueColors[$leagueName] ?? 'bg-[#1a0e40] border-[#1e164e] text-slate-300';
                    @endphp
                    <div class="p-5 hover:bg-[#1a0e40]/30 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
                            <div class="flex items-center gap-4 flex-1">
                                <div
                                    class="w-10 h-10 rounded-xl bg-[#1a0e40] border border-[#1e164e] flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-[10px] text-slate-400 mb-1">
                                        {{ $leagueName }} •
                                        {{ $match->match_date ? \Carbon\Carbon::parse($match->match_date)->format('d M Y, H:i') : 'TBD' }}
                                    </p>
                                    <h4 class="text-sm font-bold text-white mb-1.5 truncate">
                                        {{ $match->homeTeam->name ?? 'TBD' }} vs {{ $match->awayTeam->name ?? 'TBD' }}
                                    </h4>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span
                                            class="px-2 py-0.5 text-[10px] font-bold rounded border {{ $badgeClass }}">{{ $matchData['bookings_count'] }}
                                            Bookings</span>
                                        <span
                                            class="px-2 py-0.5 text-[10px] font-bold rounded border bg-[#1a0e40] border-[#1e164e] text-slate-300">{{ $matchData['capacity_percent'] }}%
                                            Capacity</span>
                                    </div>
                                </div>
                            </div>
                            <div
                                class="text-left sm:text-right flex-shrink-0 sm:pl-4 border-t sm:border-t-0 sm:border-l border-[#1e164e] pt-3 sm:pt-0">
                                <p class="text-[10px] text-slate-400 mb-0.5">Expected Revenue</p>
                                <p class="text-xl font-black font-bungee text-white">
                                    £{{ number_format($matchData['expected_revenue'] / 100, 1) }}K</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-10 text-center">
                        <svg class="w-12 h-12 text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        <p class="text-slate-400 text-sm">No upcoming matches</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Revenue Contribution Section -->
    <div>
        <h2 class="text-base font-black font-bungee text-white uppercase tracking-wider mb-4">REVENUE CONTRIBUTION</h2>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- By Branch -->
            <div x-data="{
                chart: null,
                labels: {{ json_encode($revenueByBranch['labels']) }},
                data: {{ json_encode($revenueByBranch['data']) }},
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
                        data: { labels: this.labels, datasets: [{ label: 'Revenue (£)', data: this.data, backgroundColor: '#c8ff00', borderColor: '#c8ff00', borderWidth: 1, borderRadius: 6 }] },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, grid: { color: '#1e164e', drawBorder: false }, ticks: { color: '#94a3b8', font: { size: 11 }, callback: function (v) { return '£' + v.toLocaleString(); } } },
                                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: { backgroundColor: '#0e0735', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: '#1e164e', borderWidth: 1, callbacks: { label: function (c) { return 'Revenue: £' + c.parsed.y.toLocaleString(); } } }
                            }
                        }
                    });
                }
            }" class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-sm font-black font-bungee text-white uppercase tracking-wider">BY BRANCH</h3>
                        <p class="text-xs text-slate-400 mt-1">Total revenue per branch</p>
                    </div>
                    <div
                        class="w-8 h-8 bg-[#1a0e40] border border-[#1e164e] rounded-lg flex items-center justify-center">
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

            <!-- Monthly Comparison -->
            <div x-data="{
                chart: null,
                labels: {{ json_encode($monthlyComparison['labels']) }},
                current: {{ json_encode($monthlyComparison['currentMonth']) }},
                last: {{ json_encode($monthlyComparison['lastMonth']) }},
                init() {
                    this.$nextTick(() => this.initChart());
                    this.$watch('labels', () => this.initChart());
                    this.$watch('current', () => this.initChart());
                    this.$watch('last', () => this.initChart());
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
                            datasets: [
                                { label: 'Current Period', data: this.current, backgroundColor: '#c8ff00', borderColor: '#c8ff00', borderWidth: 1, borderRadius: 6 },
                                { label: 'Previous Period', data: this.last, backgroundColor: '#1a0e40', borderColor: '#1e164e', borderWidth: 1, borderRadius: 6 }
                            ]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            scales: {
                                y: { beginAtZero: true, grid: { color: '#1e164e', drawBorder: false }, ticks: { color: '#94a3b8', font: { size: 11 }, callback: function (v) { return '£' + v.toLocaleString(); } } },
                                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } }
                            },
                            plugins: {
                                legend: { position: 'bottom', labels: { color: '#94a3b8', padding: 15, font: { size: 12 }, usePointStyle: true, pointStyle: 'circle' } },
                                tooltip: { backgroundColor: '#0e0735', titleColor: '#e2e8f0', bodyColor: '#94a3b8', borderColor: '#1e164e', borderWidth: 1, callbacks: { label: function (c) { return c.dataset.label + ': £' + c.parsed.y.toLocaleString(); } } }
                            }
                        }
                    });
                }
            }" class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6">
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-sm font-black font-bungee text-white uppercase tracking-wider">MONTHLY
                            COMPARISON</h3>
                        <p class="text-xs text-slate-400 mt-1">Current vs previous month</p>
                    </div>
                    <div
                        class="w-8 h-8 bg-[#1a0e40] border border-[#1e164e] rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                </div>
                <div wire:ignore class="relative" style="height: 300px;">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
    (function () {
        // Listen for download events from Livewire
        window.addEventListener('download-pdf', event => {
            const element = document.createElement('div');
            element.innerHTML = event.detail.html;
            const opt = {
                margin: 10,
                filename: event.detail.filename,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save();
        });

        window.addEventListener('download-csv', event => {
            const blob = new Blob([event.detail.csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', event.detail.filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    })();
</script>