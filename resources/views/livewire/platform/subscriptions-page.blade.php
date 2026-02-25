<div class="space-y-6">
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="bg-green-500/10 border border-green-500 text-green-500 px-4 py-3 rounded-lg flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 7000)"
            class="bg-red-500/10 border border-red-500 text-red-500 px-4 py-3 rounded-lg flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if (session()->has('info'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
            class="bg-blue-500/10 border border-blue-500 text-blue-500 px-4 py-3 rounded-lg flex items-start gap-3">
            <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    <!-- Header -->
    <div class="mb-8 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div class="text-center lg:text-left">
            <h1 class="text-3xl sm:text-4xl font-bungee text-white uppercase tracking-wider mb-2"
                style="text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">{{ __('platform.subscriptions.title') }}</h1>
            <p class="text-sm text-slate-400">{{ __('platform.subscriptions.subtitle') }}</p>
        </div>
        <div class="flex items-center justify-center lg:justify-end gap-2 sm:gap-3  overflow-x-auto pb-2 sm:pb-0">
            <button wire:click="exportSubscriptions"
                class="flex-shrink-0 px-4 py-2 bg-[#0a0524] border border-[#1e164e] text-slate-300 font-semibold rounded-lg hover:bg-[#1a0e40] transition-colors flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove
                    wire:target="exportSubscriptions">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                <span wire:loading.remove
                    wire:target="exportSubscriptions">{{ __('platform.subscriptions.export') }}</span>
                <span wire:loading wire:target="exportSubscriptions">{{ __('platform.subscriptions.exporting') }}</span>
            </button>
            <a href="{{ route('platform.plans') }}"
                class="flex-shrink-0 px-4 py-2 bg-[#c8ff00] hover:bg-[#d4ff33] text-black font-bold rounded-lg transition-colors flex items-center gap-2 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>{{ __('platform.subscriptions.new_plan') }}</span>
            </a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Monthly Recurring Revenue -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-6">
                <div class="w-10 h-10 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                        </path>
                    </svg>
                </div>
                <div class="text-[10px] text-slate-400 font-medium">{{ __('platform.subscriptions.this_month') }}</div>
            </div>
            <div>
                <p class="text-[32px] font-black font-bungee text-[#c8ff00] leading-none mb-2">
                    ${{ number_format($stats['mrr'], 0) }}</p>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-2">
                    {{ __('platform.subscriptions.mrr') }}
                </p>
                @if($stats['mrr_change'] > 0)
                    <span class="text-xs text-[#c8ff00] flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        +{{ $stats['mrr_change'] }}% from last month
                    </span>
                @elseif($stats['mrr_change'] < 0)
                    <span class="text-xs text-red-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                        {{ $stats['mrr_change'] }}% from last month
                    </span>
                @endif
            </div>
        </div>

        <!-- Average Revenue Per Cafe -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-6">
                <div class="w-10 h-10 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
                <div class="text-[10px] text-slate-400 font-medium">{{ __('platform.subscriptions.per_cafe') }}</div>
            </div>
            <div>
                <p class="text-[32px] font-black font-bungee text-[#c8ff00] leading-none mb-2">
                    ${{ number_format($stats['arpc'], 0) }}</p>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-2">
                    {{ __('platform.subscriptions.arpu') }}
                </p>
                @if($stats['arpc_change'] > 0)
                    <span class="text-xs text-[#c8ff00] flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                        </svg>
                        +{{ $stats['arpc_change'] }}% from last month
                    </span>
                @elseif($stats['arpc_change'] < 0)
                    <span class="text-xs text-red-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                        {{ $stats['arpc_change'] }}% from last month
                    </span>
                @endif
            </div>
        </div>

        <!-- Churn Rate -->
        <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-6 flex flex-col justify-between">
            <div class="flex items-start justify-between mb-6">
                <div class="w-10 h-10 bg-[#1a0e40] border border-[#1e164e] rounded-xl flex items-center justify-center">
                    <svg class="w-5 h-5 text-[#c8ff00]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                </div>
                <div class="text-[10px] text-slate-400 font-medium">{{ __('platform.subscriptions.monthly') }}</div>
            </div>
            <div>
                <p class="text-[32px] font-black font-bungee text-[#c8ff00] leading-none mb-2">
                    {{ number_format($stats['churn_rate'], 1) }}%
                </p>
                <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-2">
                    {{ __('platform.subscriptions.churn_rate') }}
                </p>
                @if($stats['churn_rate'] < 5)
                    <span class="text-xs text-red-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                        </svg>
                        -1.1% from last month
                    </span>
                @else
                    <span class="text-xs text-orange-400">Monitor Closely</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Revenue Trend Chart -->
    <div x-data="{
        chart: null,
        labels: {{ json_encode($revenueTrend['labels']) }},
        data: {{ json_encode($revenueTrend['data']) }},
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
                        label: 'Revenue',
                        data: this.data,
                        borderColor: '#c8ff00',
                        backgroundColor: 'rgba(200, 255, 0, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#c8ff00',
                        pointBorderColor: '#0f172a',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: '#334155', drawBorder: false },
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 11 },
                                callback: value => '$' + value.toLocaleString()
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: '#94a3b8', font: { size: 11 } }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleColor: '#e2e8f0',
                            bodyColor: '#94a3b8',
                            borderColor: '#334155',
                            borderWidth: 1,
                            callbacks: {
                                label: ctx => 'Revenue: $' + ctx.parsed.y.toLocaleString()
                            }
                        }
                    }
                }
            });
        }
    }" class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-4 sm:p-6 overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
            <div class="text-center lg:text-left">
                <h3 class="text-base font-black font-bungee text-white uppercase tracking-wider">
                    {{ __('platform.subscriptions.revenue_trend') }}
                </h3>
                <p class="text-xs text-slate-400 mt-1">{{ __('platform.subscriptions.last_6_months') }}</p>
            </div>
            <div class="flex gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                <button wire:click="$set('period', '6M')"
                    class="px-3 py-1 flex-shrink-0 {{ $period === '6M' ? 'bg-[#c8ff00] text-black font-bold' : 'bg-[#1a0e40] text-slate-400 border border-[#1e164e]' }} rounded-lg text-xs font-semibold transition-colors">6M</button>
                <button wire:click="$set('period', '1Y')"
                    class="px-3 py-1 flex-shrink-0 {{ $period === '1Y' ? 'bg-[#c8ff00] text-black font-bold' : 'bg-[#1a0e40] text-slate-400 border border-[#1e164e]' }} rounded-lg text-xs font-semibold transition-colors">1Y</button>
                <button wire:click="$set('period', 'ALL')"
                    class="px-3 py-1 flex-shrink-0 {{ $period === 'ALL' ? 'bg-[#c8ff00] text-black font-bold' : 'bg-[#1a0e40] text-slate-400 border border-[#1e164e]' }} rounded-lg text-xs font-semibold transition-colors">ALL</button>
            </div>
        </div>
        <div wire:ignore class="relative w-full overflow-hidden" style="height: 300px;">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    <!-- Subscription Plans -->
    <div>
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
            <h2 class="text-xl font-black font-bungee text-white uppercase tracking-wider text-center lg:text-left"
                style="text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">{{ __('platform.subscriptions.plans_title') }}</h2>
            <a href="{{ route('platform.plans') }}"
                class="text-[#c8ff00] text-sm font-bold hover:underline flex items-center gap-1">
                {{ __('platform.subscriptions.manage_plans') }}
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                    </path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($plans as $plan)
                <div
                    class="bg-[#0e0735] border {{ $plan->is_popular ? 'border-[#c8ff00]' : 'border-[#1e164e]' }} rounded-xl overflow-hidden relative">
                    @if($plan->is_popular)
                        <div class="absolute top-3 right-3 z-10">
                            <span
                                class="px-3 py-1 bg-[#c8ff00] text-black text-[10px] font-black font-bungee uppercase rounded-full">{{ __('platform.subscriptions.popular') }}</span>
                        </div>
                    @endif
                    <!-- Cover Image -->
                    <div class="relative h-40 overflow-hidden">
                        @if($plan->slug === 'starter')
                            <div class="absolute inset-0 bg-cover bg-center"
                                style="background-image: url('https://images.unsplash.com/photo-1556056504-5c7696c4c328?w=600&auto=format&fit=crop&q=60');">
                            </div>
                        @elseif($plan->slug === 'pro')
                            <div class="absolute inset-0 bg-cover bg-center"
                                style="background-image: url('https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=600&auto=format&fit=crop&q=60');">
                            </div>
                        @else
                            <div class="absolute inset-0 bg-cover bg-center"
                                style="background-image: url('https://images.unsplash.com/photo-1522778119026-d647f0596c20?w=600&auto=format&fit=crop&q=60');">
                            </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0e0735]/90 via-[#0e0735]/30 to-transparent">
                        </div>
                        <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-[#0e0735] to-transparent"></div>
                    </div>

                    <!-- Plan Details -->
                    <div class="p-5">
                        <!-- Plan badge + price row -->
                        <div class="flex items-center justify-between mb-3">
                            @if($plan->slug === 'starter')
                                <span
                                    class="px-3 py-1 bg-[#1a0e40] border border-[#1e164e] text-white text-[10px] font-black font-bungee rounded uppercase">{{ __('platform.subscriptions.starter') }}</span>
                            @elseif($plan->slug === 'pro')
                                <span
                                    class="px-3 py-1 bg-[#c8ff00] text-black text-[10px] font-black font-bungee rounded uppercase">{{ __('platform.subscriptions.pro') }}</span>
                            @else
                                <span
                                    class="px-3 py-1 bg-[#1a0e40] border border-[#1e164e] text-[#c8ff00] text-[10px] font-black font-bungee rounded uppercase">{{ __('platform.subscriptions.enterprise') }}</span>
                            @endif
                            <span
                                class="text-2xl font-black font-bungee text-white">${{ number_format($plan->price, 0) }}</span>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-slate-400 mb-4">
                            @if($plan->slug === 'starter') {{ __('platform.subscriptions.plan_starter_desc') }}
                            @elseif($plan->slug === 'pro') {{ __('platform.subscriptions.plan_pro_desc') }}
                            @else {{ __('platform.subscriptions.plan_enterprise_desc') }}
                            @endif
                        </p>

                        <!-- Features -->
                        <ul class="space-y-2 mb-4">
                            @if(is_array($plan->features) && count($plan->features) > 0)
                                @foreach($plan->features as $feature)
                                    <li class="flex items-start gap-2 text-xs text-slate-300">
                                        <svg class="w-4 h-4 text-[#c8ff00] flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        {{ $feature }}
                                    </li>
                                @endforeach
                            @else
                                <li class="text-xs text-slate-500 italic">{{ __('platform.subscriptions.no_features') }}</li>
                            @endif
                        </ul>

                        <!-- Active Cafes & Actions -->
                        <div class="pt-4 border-t border-[#1e164e] flex items-center justify-between">
                            <div>
                                <span
                                    class="text-[10px] text-slate-400 uppercase tracking-widest">{{ __('platform.subscriptions.active_cafes') }}</span>
                                <p class="text-xl font-black font-bungee text-white">{{ $plan->active_cafes }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Active Subscriptions Table -->
    <div class="bg-[#0e0735] border border-[#1e164e] rounded-xl p-4 sm:p-6 overflow-hidden">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
            <div class="text-center  lg:text-left">
                <h2 class="text-base mb-0 font-black font-bungee text-white uppercase tracking-wider">
                    {{ __('platform.subscriptions.active_title') }}
                </h2>

            </div>
            <div class="flex-co sm:flex-row items-center gap-3">
                <div class="relative w-full sm:w-auto">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" wire:model.live="search"
                        placeholder="{{ __('platform.subscriptions.search_cafes') }}"
                        class="pl-9 pr-4 py-2 bg-[#0a0524] border border-[#1e164e] text-white text-sm rounded-lg focus:outline-none focus:border-[#c8ff00] transition-colors w-full sm:w-52">
                </div>

            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-[#1e164e]">
                        <th
                            class="text-left py-3 px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.subscriptions.cafe') }}
                        </th>
                        <th
                            class="text-left py-3 px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.subscriptions.plan_col') }}
                        </th>
                        <th
                            class="text-left py-3 px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.subscriptions.mrr_col') }}
                        </th>
                        <th
                            class="text-left py-3 px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.subscriptions.renewal_date') }}
                        </th>
                        <th
                            class="text-left py-3 px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.subscriptions.status') }}
                        </th>
                        <th
                            class="text-right py-3 px-4 text-[10px] font-semibold text-slate-400 uppercase tracking-widest">
                            {{ __('platform.subscriptions.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($subscriptions as $subscription)
                        <tr class="border-b border-[#1e164e] hover:bg-[#1a0e40]/30 transition-colors">
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-[#1a0e40] border border-[#1e164e] flex items-center justify-center text-xs font-black text-[#c8ff00] font-bungee flex-shrink-0">
                                        {{ strtoupper(substr($subscription->cafe->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <p class="text-white font-semibold text-sm">{{ $subscription->cafe->name }}</p>
                                        <p class="text-xs text-slate-400">{{ $subscription->cafe->city ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-4 px-4">
                                @if($subscription->plan->slug === 'starter')
                                    <span
                                        class="px-2.5 py-1 bg-[#1a0e40] border border-[#1e164e] text-white text-[10px] font-black font-bungee rounded">{{ __('platform.subscriptions.starter') }}</span>
                                @elseif($subscription->plan->slug === 'pro')
                                    <span
                                        class="px-2.5 py-1 bg-[#c8ff00] text-black text-[10px] font-black font-bungee rounded">{{ __('platform.subscriptions.pro') }}</span>
                                @else
                                    <span
                                        class="px-2.5 py-1 bg-[#1a0e40] border border-[#c8ff00]/30 text-[#c8ff00] text-[10px] font-black font-bungee rounded">{{ __('platform.subscriptions.enterprise') }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-4">
                                <span
                                    class="text-white font-bold text-sm">${{ number_format($subscription->plan->price, 0) }}</span>
                            </td>
                            <td class="py-4 px-4">
                                <span
                                    class="text-slate-300 text-sm">{{ $subscription->expires_at->format('M d, Y') }}</span>
                            </td>
                            <td class="py-4 px-4">
                                @if($subscription->is_expiring_soon)
                                    <span
                                        class="px-2.5 py-1 bg-orange-900/30 border border-orange-700/40 text-orange-400 text-[10px] font-bold rounded">{{ __('platform.subscriptions.expiring_soon') }}</span>
                                @else
                                    <span
                                        class="px-2.5 py-1 bg-green-900/30 border border-green-700/40 text-green-400 text-[10px] font-bold rounded">{{ __('platform.subscriptions.active_status') }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-4 text-right">
                                <a href="/platform/cafes/{{ $subscription->cafe_id }}"
                                    class="inline-flex items-center justify-center w-8 h-8 bg-[#1a0e40] border border-[#1e164e] hover:bg-[#1e164e] rounded-lg transition-colors">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 text-sm">
                                No active subscriptions found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $subscriptions->links() }}
        </div>
    </div>


</div>