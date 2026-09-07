@php $isAr = app()->getLocale() === 'ar'; @endphp
<a href="{{ request()->fullUrlWithQuery(['lang' => $isAr ? 'en' : 'ar']) }}"
   class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-brand-border text-sm font-semibold text-slate-300 hover:text-white hover:border-slate-500 transition">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.6 9h16.8M3.6 15h16.8M11.5 3a17 17 0 000 18M12.5 3a17 17 0 010 18"></path>
    </svg>
    {{ $isAr ? 'English' : 'العربية' }}
</a>
