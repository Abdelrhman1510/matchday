<div>
    <h1 class="text-3xl font-bold text-white uppercase tracking-wide mb-6">
        {{ __('platform.nav.bookings') ?? 'BOOKINGS MANAGEMENT' }}</h1>

    <div class="bg-[#1e293b] border border-slate-700 rounded-xl p-8 text-center">
        <p class="text-slate-400 mb-4">
            {{ __('platform.common.coming_soon') ?? 'Bookings management interface coming soon' }}</p>
        <p class="text-sm text-slate-500">{{ __('platform.common.total') ?? 'Total:' }} {{ $bookings->total() }}
            {{ __('platform.nav.bookings') ?? 'bookings' }}</p>
    </div>
</div>