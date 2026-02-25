<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row justify-between items-center gap-4 mb-8">
        <div class="text-center lg:text-left">
            <h1 class="text-2xl sm:text-3xl font-bold text-white uppercase tracking-wide">{{ __('platform.reports.title') }}</h1>
            <p class="text-slate-400 mt-1">{{ __('platform.reports.subtitle') }}</p>
        </div>
        <div class="flex gap-2 sm:gap-3 overflow-x-auto justify-center lg:justify-end pb-2 sm:pb-0">
            <button wire:click="exportPDF" 
                class="flex-shrink-0 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors flex items-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportPDF">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span wire:loading.remove wire:target="exportPDF">{{ __('platform.reports.export_pdf') }}</span>
                <span wire:loading wire:target="exportPDF">{{ __('platform.reports.exporting') }}</span>
            </button>
            <button wire:click="exportCSV" 
                class="flex-shrink-0 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors flex items-center gap-2 text-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportCSV">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                <span wire:loading.remove wire:target="exportCSV">{{ __('platform.reports.export_excel') }}</span>
                <span wire:loading wire:target="exportCSV">{{ __('platform.reports.exporting') }}</span>
            </button>
        </div>
    </div>

    {{-- Report Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        {{-- Booking Report Card --}}
        <div class="bg-[#1e293b] border border-slate-700 rounded-xl overflow-hidden hover:border-[#c8ff00] transition-colors">
            <div class="h-32 bg-gradient-to-br from-blue-600 to-blue-800 relative overflow-hidden">
                <div class="absolute inset-0 opacity-20">
                    <svg class="w-full h-full" viewBox="0 0 200 200" fill="currentColor">
                        <path d="M40 60h120v80H40z" opacity="0.3"/>
                        <path d="M60 40h80v120H60z" opacity="0.5"/>
                    </svg>
                </div>
                <div class="absolute bottom-4 left-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <h3 class="text-lg font-semibold text-white mb-2">{{ __('platform.reports.booking_report') }}</h3>
                <p class="text-slate-400 text-sm mb-4">{{ __('platform.reports.booking_report_desc', ['days' => $period]) }}</p>
                
                <div class="space-y-3">
                    <div>
                        <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">{{ __('platform.reports.total_bookings') }}</div>
                        <div class="text-2xl font-bold text-white">{{ number_format($bookingReport['total_bookings']) }}</div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">{{ __('platform.reports.avg_duration') }}</div>
                            <div class="text-lg font-semibold text-white">{{ $bookingReport['avg_duration'] }}h</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">{{ __('platform.reports.completion') }}</div>
                            <div class="text-lg font-semibold text-[#c8ff00]">{{ $bookingReport['completion_rate'] }}%</div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2 mt-4">
                    <button wire:click="downloadReport('bookings', 'csv')" class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded transition-colors">
                        <span wire:loading.remove wire:target="downloadReport">CSV</span>
                        <span wire:loading wire:target="downloadReport">...</span>
                    </button>
                    <button wire:click="downloadReport('bookings', 'pdf')" class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs rounded transition-colors">
                        <span wire:loading.remove wire:target="downloadReport">PDF</span>
                        <span wire:loading wire:target="downloadReport">...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Revenue Report Card --}}
        <div class="bg-[#1e293b] border border-slate-700 rounded-xl overflow-hidden hover:border-[#c8ff00] transition-colors">
            <div class="h-32 bg-gradient-to-br from-green-600 to-emerald-800 relative overflow-hidden">
                <div class="absolute inset-0 opacity-20">
                    <svg class="w-full h-full" viewBox="0 0 200 200" fill="currentColor">
                        <circle cx="100" cy="100" r="40" opacity="0.3"/>
                        <circle cx="100" cy="100" r="60" opacity="0.2"/>
                        <circle cx="100" cy="100" r="80" opacity="0.1"/>
                    </svg>
                </div>
                <div class="absolute bottom-4 left-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <h3 class="text-lg font-semibold text-white mb-2">{{ __('platform.reports.revenue_report') }}</h3>
                <p class="text-slate-400 text-sm mb-4">{{ __('platform.reports.revenue_report_desc', ['days' => $period]) }}</p>
                
                <div class="space-y-3">
                    <div>
                        <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">{{ __('platform.reports.total_revenue') }}</div>
                        <div class="text-2xl font-bold text-white">${{ number_format($revenueReport['total_revenue'] / 100, 2) }}</div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">{{ __('platform.reports.avg_transaction') }}</div>
                            <div class="text-lg font-semibold text-white">${{ number_format($revenueReport['avg_transaction'] / 100, 2) }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">{{ __('platform.reports.growth_rate') }}</div>
                            <div class="text-lg font-semibold {{ $revenueReport['growth_rate'] >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $revenueReport['growth_rate'] > 0 ? '+' : '' }}{{ $revenueReport['growth_rate'] }}%
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2 mt-4">
                    <button wire:click="downloadReport('revenue', 'csv')" class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded transition-colors">
                        <span wire:loading.remove wire:target="downloadReport">CSV</span>
                        <span wire:loading wire:target="downloadReport">...</span>
                    </button>
                    <button wire:click="downloadReport('revenue', 'pdf')" class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs rounded transition-colors">
                        <span wire:loading.remove wire:target="downloadReport">PDF</span>
                        <span wire:loading wire:target="downloadReport">...</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Cafe Performance Card --}}
        <div class="bg-[#1e293b] border border-slate-700 rounded-xl overflow-hidden hover:border-[#c8ff00] transition-colors">
            <div class="h-32 bg-gradient-to-br from-purple-600 to-violet-800 relative overflow-hidden">
                <div class="absolute inset-0 opacity-20">
                    <svg class="w-full h-full" viewBox="0 0 200 200" fill="currentColor">
                        <rect x="50" y="60" width="30" height="80" opacity="0.3"/>
                        <rect x="85" y="40" width="30" height="100" opacity="0.5"/>
                        <rect x="120" y="70" width="30" height="70" opacity="0.3"/>
                    </svg>
                </div>
                <div class="absolute bottom-4 left-4">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-sm rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="p-6">
                <h3 class="text-lg font-semibold text-white mb-2">{{ __('platform.reports.cafe_performance') }}</h3>
                <p class="text-slate-400 text-sm mb-4">{{ __('platform.reports.cafe_perf_desc') }}</p>
                
                <div class="space-y-3">
                    <div>
                        <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">{{ __('platform.reports.active_cafes') }}</div>
                        <div class="text-2xl font-bold text-white">{{ number_format($cafePerformance['active_cafes']) }}</div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">{{ __('platform.reports.avg_rating') }}</div>
                            <div class="text-lg font-semibold text-white flex items-center gap-1">
                                {{ $cafePerformance['avg_rating'] }}
                                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 uppercase tracking-wide mb-1">{{ __('platform.reports.occupancy') }}</div>
                            <div class="text-lg font-semibold text-[#c8ff00]">{{ $cafePerformance['occupancy_rate'] }}%</div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-2 mt-4">
                    <button wire:click="downloadReport('cafe-performance', 'csv')" class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded transition-colors">
                        <span wire:loading.remove wire:target="downloadReport">CSV</span>
                        <span wire:loading wire:target="downloadReport">...</span>
                    </button>
                    <button wire:click="downloadReport('cafe-performance', 'pdf')" class="flex-1 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs rounded transition-colors">
                        <span wire:loading.remove wire:target="downloadReport">PDF</span>
                        <span wire:loading wire:target="downloadReport">...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Booking Trends Chart --}}
        <div x-data="{
            chart: null,
            labels: {{ json_encode($bookingTrends['labels']) }},
            data: {{ json_encode($bookingTrends['data']) }},
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
                            label: 'Bookings',
                            data: this.data,
                            borderColor: '#c8ff00',
                            backgroundColor: 'rgba(200, 255, 0, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            pointBackgroundColor: '#c8ff00',
                            pointBorderColor: '#c8ff00',
                            pointRadius: 3,
                            pointHoverRadius: 5,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e293b', titleColor: '#f8fafc',
                                bodyColor: '#cbd5e1', borderColor: '#334155',
                                borderWidth: 1, padding: 12, displayColors: false,
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#334155', drawBorder: false }, ticks: { color: '#94a3b8', precision: 0 } },
                            x: { grid: { display: false }, ticks: { color: '#94a3b8', maxRotation: 45, minRotation: 45 } }
                        }
                    }
                });
            }
        }" class="bg-[#1e293b] border border-slate-700 rounded-xl p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-white">{{ __('platform.reports.booking_trends') }}</h3>
                    <p class="text-sm text-slate-400">{{ __('platform.common.last_30_days') ?? 'Last 30 days' }}</p>
                </div>
            </div>
            <div wire:ignore class="relative h-64">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Revenue Breakdown Chart --}}
        <div x-data="{
            chart: null,
            labels: {{ json_encode($revenueBreakdown['labels']) }},
            data: {{ json_encode($revenueBreakdown['data']) }},
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
                
                const revenueData = this.data.map(v => v / 100);
                
                this.chart = new window.Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: this.labels,
                        datasets: [{ data: revenueData, backgroundColor: ['#c8ff00', '#3b82f6', '#8b5cf6'], borderColor: '#1e293b', borderWidth: 2 }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right', labels: { color: '#cbd5e1', padding: 15, font: { size: 12 }, usePointStyle: true, pointStyle: 'circle' } },
                            tooltip: {
                                backgroundColor: '#1e293b', titleColor: '#f8fafc',
                                bodyColor: '#cbd5e1', borderColor: '#334155', borderWidth: 1, padding: 12,
                                callbacks: { label: ctx => ctx.label + ': $' + ctx.parsed.toLocaleString('en-US', { minimumFractionDigits: 2 }) }
                            }
                        }
                    }
                });
            }
        }" class="bg-[#1e293b] border border-slate-700 rounded-xl p-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-white">{{ __('platform.reports.revenue_breakdown') }}</h3>
                    <p class="text-sm text-slate-400">{{ __('platform.reports.by_payment_type') }}</p>
                </div>
            </div>
            <div wire:ignore class="relative h-64">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    {{-- Top Performing Cafes Chart --}}
    <div x-data="{
        chart: null,
        labels: {{ json_encode($topCafes['labels']) }},
        data: {{ json_encode($topCafes['data']) }},
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
            
            const revenueData = this.data.map(v => v / 100);
            
            this.chart = new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels: this.labels,
                    datasets: [{ label: 'Revenue ($)', data: revenueData, backgroundColor: '#c8ff00', borderRadius: 6, barThickness: 40 }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1e293b', titleColor: '#f8fafc',
                            bodyColor: '#cbd5e1', borderColor: '#334155', borderWidth: 1, padding: 12,
                            callbacks: { label: ctx => 'Revenue: $' + ctx.parsed.x.toLocaleString('en-US', { minimumFractionDigits: 2 }) }
                        }
                    },
                    scales: {
                        x: { beginAtZero: true, grid: { color: '#334155', drawBorder: false }, ticks: { color: '#94a3b8', callback: v => '$' + v.toLocaleString() } },
                        y: { grid: { display: false }, ticks: { color: '#94a3b8' } }
                    }
                }
            });
        }
    }" class="bg-[#1e293b] border border-slate-700 rounded-xl p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="text-lg font-semibold text-white">{{ __('platform.reports.top_cafes') }}</h3>
                <p class="text-sm text-slate-400">{{ __('platform.reports.by_revenue', ["days" => $period]) }}</p>
            </div>
        </div>
        <div wire:ignore class="relative h-80">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>
</div>
