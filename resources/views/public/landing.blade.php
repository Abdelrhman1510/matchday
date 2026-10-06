<!DOCTYPE html>
@php $isAr = app()->getLocale() === 'ar'; @endphp
<html lang="{{ app()->getLocale() }}" dir="{{ $isAr ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shaj3 — {{ $isAr ? 'احجز مقعدك، عِش المباراة' : 'Book your seat, feel the match' }}</title>
    <meta name="description" content="{{ __('site.hero.sub') }}">
    <link rel="icon" href="{{ asset('images/shaja3_icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Bungee&family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    'brand-dark':  '#07021e', 'brand-main': '#0c0628',
                    'brand-card':  '#110830', 'brand-border': '#1e164e',
                    'brand-accent':'#c8ff00',
                },
                fontFamily: { sans: ['Instrument Sans','sans-serif'], bungee: ['Bungee','cursive'], cairo: ['Cairo','sans-serif'] },
            }}
        }
    </script>
    <style>
        body { background-color:#0c0628; font-family:'Instrument Sans',sans-serif; -webkit-font-smoothing:antialiased; }
        html[dir="rtl"] body { font-family:'Cairo','Instrument Sans',sans-serif; }
        .glow::before {
            content:""; position:absolute; inset:-20% 0 auto 0; height:520px; z-index:0;
            background: radial-gradient(600px 300px at 50% 0%, rgba(200,255,0,.14), transparent 70%);
            pointer-events:none;
        }
        .card-hover { transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease; }
        .card-hover:hover { transform: translateY(-4px); border-color:#3a2e6e; box-shadow:0 20px 40px -20px rgba(0,0,0,.6); }
        .cta { box-shadow: 0 10px 30px -8px rgba(200,255,0,.35); }
        .cta:hover { box-shadow: 0 14px 36px -8px rgba(200,255,0,.5); }
    </style>
</head>
<body class="text-slate-200">

    <!-- Nav -->
    <header class="sticky top-0 z-30 backdrop-blur-md bg-brand-main/70 border-b border-brand-border/60">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/shaja3_icon.png') }}" alt="Shaj3" class="w-9 h-9 rounded-lg object-cover">
                <span class="font-bungee text-white text-lg tracking-wide">SHAJ3</span>
            </a>
            <nav class="flex items-center gap-4 sm:gap-6 text-sm font-semibold text-slate-300">
                <a href="#features" class="hidden sm:inline hover:text-white transition-colors">{{ __('site.nav.features') }}</a>
                <a href="#how" class="hidden sm:inline hover:text-white transition-colors">{{ __('site.nav.how') }}</a>
                @include('public.partials.lang-switch')
                <a href="{{ route('public.contact') }}" class="px-4 py-2 rounded-lg bg-brand-accent text-brand-dark hover:opacity-90 transition">{{ __('site.nav.contact') }}</a>
            </nav>
        </div>
    </header>

    <main>
        <!-- Hero -->
        <section class="glow relative overflow-hidden">
            <div class="relative z-10 max-w-3xl mx-auto px-6 text-center pt-20 pb-16 sm:pt-28 sm:pb-24">
                <img src="{{ asset('images/shaja3_icon.png') }}" alt="Shaj3" class="w-20 h-20 mx-auto rounded-2xl object-cover ring-1 ring-brand-border shadow-2xl">
                <span class="inline-block mt-8 px-3 py-1 rounded-full border border-brand-border text-brand-accent text-xs font-bold uppercase tracking-widest">{{ __('site.hero.badge') }}</span>
                <h1 class="font-bungee text-4xl sm:text-6xl text-white leading-[1.05] mt-6 {{ $isAr ? 'font-cairo font-extrabold' : '' }}">{{ __('site.hero.title1') }}<br><span class="text-brand-accent">{{ __('site.hero.title2') }}</span></h1>
                <p class="max-w-xl mx-auto mt-6 text-lg text-slate-400">{{ __('site.hero.sub') }}</p>
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                    <a href="{{ route('public.contact') }}" class="cta w-full sm:w-auto px-10 py-5 rounded-2xl bg-brand-accent text-brand-dark font-bold text-lg transition">{{ __('site.hero.cta1') }}</a>
                    <a href="#how" class="w-full sm:w-auto px-10 py-5 rounded-2xl border border-brand-border text-white font-semibold text-lg hover:border-slate-500 hover:bg-brand-card transition">{{ __('site.hero.cta2') }}</a>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="max-w-6xl mx-auto px-6 py-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ([
                    ['⚽', __('site.features.live_t'), __('site.features.live_d')],
                    ['🎟️', __('site.features.seats_t'), __('site.features.seats_d')],
                    ['🏆', __('site.features.loyalty_t'), __('site.features.loyalty_d')],
                    ['💬', __('site.features.fanroom_t'), __('site.features.fanroom_d')],
                ] as [$icon, $title, $desc])
                    <div class="card-hover bg-brand-card border border-brand-border rounded-2xl p-6">
                        <div class="w-12 h-12 flex items-center justify-center rounded-xl bg-brand-accent/10 text-2xl mb-4">{{ $icon }}</div>
                        <h3 class="font-bold text-white mb-1.5">{{ $title }}</h3>
                        <p class="text-sm leading-relaxed text-slate-400">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- How it works -->
        <section id="how" class="max-w-6xl mx-auto px-6 py-12">
            <h2 class="font-bungee text-2xl sm:text-3xl text-white text-center {{ $isAr ? 'font-cairo font-extrabold' : '' }}">{{ __('site.how.heading') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-10">
                @foreach ([
                    ['01', __('site.how.s1_t'), __('site.how.s1_d')],
                    ['02', __('site.how.s2_t'), __('site.how.s2_d')],
                    ['03', __('site.how.s3_t'), __('site.how.s3_d')],
                ] as [$n, $title, $desc])
                    <div class="relative bg-brand-card border border-brand-border rounded-2xl p-7">
                        <span class="font-bungee text-4xl text-brand-accent/30">{{ $n }}</span>
                        <h3 class="font-bold text-white mt-2 mb-1.5">{{ $title }}</h3>
                        <p class="text-sm leading-relaxed text-slate-400">{{ $desc }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <!-- Café CTA -->
        <section class="max-w-6xl mx-auto px-6 py-12">
            <div class="relative overflow-hidden rounded-3xl border border-brand-border bg-gradient-to-br from-brand-card to-brand-dark px-8 py-14 text-center">
                <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[420px] h-[240px] rounded-full bg-brand-accent/10 blur-3xl"></div>
                <div class="relative">
                    <h2 class="font-bungee text-2xl sm:text-3xl text-white {{ $isAr ? 'font-cairo font-extrabold' : '' }}">{{ __('site.cafe.heading') }}</h2>
                    <p class="max-w-lg mx-auto mt-3 text-slate-400">{{ __('site.cafe.sub') }}</p>
                    <a href="{{ route('public.contact') }}" class="cta inline-block mt-8 px-7 py-3.5 rounded-xl bg-brand-accent text-brand-dark font-bold transition">{{ __('site.cafe.cta') }}</a>
                </div>
            </div>
        </section>
    </main>

    @include('public.partials.footer')
</body>
</html>
