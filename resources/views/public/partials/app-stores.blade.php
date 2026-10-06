@php $isAr = app()->getLocale() === 'ar'; @endphp
<div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-3">
    {{-- Google Play --}}
    <a href="https://play.google.com/store/apps/details?id=com.ybn.tab3" target="_blank" rel="noopener"
       class="inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-black border border-brand-border hover:border-slate-500 transition-colors w-56 sm:w-auto justify-center">
        <svg class="w-7 h-7 shrink-0" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M48 59v394c0 10 8 15 16 10l240-207L64 49c-8-5-16 0-16 10z" fill="#00d2ff"/>
            <path d="M304 256L64 49c-8-5-16 0-16 10l1 2 231 229 24-34z" fill="#00f076"/>
            <path d="M304 256l-24 34L49 453l-1 2c0 10 8 15 16 10l240-207z" fill="#ff3a44"/>
            <path d="M400 229l-72-42-48 69 48 69 72-42c15-9 15-45 0-54z" fill="#ffce00"/>
        </svg>
        <span class="text-left leading-tight">
            <span class="block text-[10px] text-slate-400 uppercase tracking-wide">{{ $isAr ? 'احصل عليه من' : 'Get it on' }}</span>
            <span class="block text-white font-semibold text-base -mt-0.5">Google Play</span>
        </span>
    </a>

    {{-- App Store (coming soon — no iOS link yet) --}}
    <span class="relative inline-flex items-center gap-3 px-5 py-3 rounded-xl bg-black border border-brand-border opacity-60 cursor-default w-56 sm:w-auto justify-center">
        <svg class="w-7 h-7 shrink-0 text-white" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.05 12.54c-.03-2.6 2.12-3.85 2.22-3.91-1.21-1.77-3.09-2.01-3.76-2.04-1.6-.16-3.12.94-3.93.94-.81 0-2.06-.92-3.39-.9-1.74.03-3.35 1.01-4.25 2.57-1.81 3.14-.46 7.79 1.3 10.33.86 1.24 1.89 2.64 3.23 2.59 1.3-.05 1.79-.84 3.36-.84 1.57 0 2.01.84 3.38.81 1.4-.02 2.28-1.27 3.13-2.52.99-1.44 1.4-2.84 1.42-2.91-.03-.01-2.72-1.04-2.75-4.13zM14.6 4.84c.71-.86 1.19-2.06 1.06-3.25-1.02.04-2.26.68-2.99 1.54-.66.76-1.23 1.98-1.08 3.15 1.14.09 2.3-.58 3.01-1.44z"/>
        </svg>
        <span class="text-left leading-tight">
            <span class="block text-[10px] text-slate-400 uppercase tracking-wide">{{ $isAr ? 'حمّله من' : 'Download on the' }}</span>
            <span class="block text-white font-semibold text-base -mt-0.5">App Store</span>
        </span>
        <span class="absolute -top-2 {{ $isAr ? 'left-2' : 'right-2' }} px-1.5 py-0.5 rounded bg-brand-accent text-brand-dark text-[9px] font-bold uppercase">{{ $isAr ? 'قريباً' : 'Soon' }}</span>
    </span>
</div>
