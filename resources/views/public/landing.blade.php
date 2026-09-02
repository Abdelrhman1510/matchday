<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shaj3 — Book your seat, feel the match</title>
    <meta name="description" content="Shaj3 lets fans reserve seats at cafés to watch live football matches together — pick your match, choose your seat, and enjoy the game.">
    <link rel="icon" href="{{ asset('images/shaja3_icon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700;800&family=Bungee&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: { extend: {
                colors: {
                    'brand-dark':  '#07021e', 'brand-main': '#0c0628',
                    'brand-card':  '#110830', 'brand-border': '#1e164e',
                    'brand-accent':'#c8ff00',
                },
                fontFamily: { sans: ['Instrument Sans','sans-serif'], bungee: ['Bungee','cursive'] },
            }}
        }
    </script>
    <style>
        body { background-color:#0c0628; font-family:'Instrument Sans',sans-serif; -webkit-font-smoothing:antialiased; }
        /* soft lime glow behind the hero */
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
                <span class="font-bungee text-white text-lg tracking-wide">Shaj3</span>
            </a>
            <nav class="flex items-center gap-6 text-sm font-semibold text-slate-300">
                <a href="#features" class="hidden sm:inline hover:text-white transition-colors">Features</a>
                <a href="#how" class="hidden sm:inline hover:text-white transition-colors">How it works</a>
                <a href="{{ route('public.contact') }}" class="px-4 py-2 rounded-lg bg-brand-accent text-brand-dark hover:opacity-90 transition">Contact</a>
            </nav>
        </div>
    </header>

    <main>
        <!-- Hero -->
        <section class="glow relative overflow-hidden">
            <div class="relative z-10 max-w-3xl mx-auto px-6 text-center pt-20 pb-16 sm:pt-28 sm:pb-24">
                <img src="{{ asset('images/shaja3_icon.png') }}" alt="Shaj3" class="w-20 h-20 mx-auto rounded-2xl object-cover ring-1 ring-brand-border shadow-2xl">
                <span class="inline-block mt-8 px-3 py-1 rounded-full border border-brand-border text-brand-accent text-xs font-bold uppercase tracking-widest">Watch football, together</span>
                <h1 class="font-bungee text-4xl sm:text-6xl text-white leading-[1.05] mt-6">Book your seat.<br><span class="text-brand-accent">Feel the match.</span></h1>
                <p class="max-w-xl mx-auto mt-6 text-lg text-slate-400">Reserve a spot at your favourite café to watch live matches on the big screen. Pick the game, choose your seat, and show up ready to cheer.</p>
                <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('public.contact') }}" class="cta w-full sm:w-auto px-7 py-3.5 rounded-xl bg-brand-accent text-brand-dark font-bold transition">Get in touch</a>
                    <a href="#how" class="w-full sm:w-auto px-7 py-3.5 rounded-xl border border-brand-border text-white font-semibold hover:border-slate-500 transition">How it works</a>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="max-w-6xl mx-auto px-6 py-12">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ([
                    ['⚽', 'Live matches', 'Browse upcoming games and see which cafés are showing them.'],
                    ['🎟️', 'Reserve seats', 'Choose your section and seats, then confirm with a QR code for entry.'],
                    ['🏆', 'Loyalty rewards', 'Earn points on every booking and climb from Bronze to Platinum.'],
                    ['💬', 'Fan Room', 'Chat live with other fans watching the same match at your venue.'],
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
            <h2 class="font-bungee text-2xl sm:text-3xl text-white text-center">Three steps to kickoff</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-10">
                @foreach ([
                    ['01', 'Pick a match', 'Find an upcoming game and a café showing it near you.'],
                    ['02', 'Choose your seat', 'Select a section and your seats, then confirm the booking.'],
                    ['03', 'Show your QR', 'Arrive, scan your entry pass, and enjoy the match.'],
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
                    <h2 class="font-bungee text-2xl sm:text-3xl text-white">Own a café?</h2>
                    <p class="max-w-lg mx-auto mt-3 text-slate-400">List your branches, publish the matches you're showing, and fill your seats. Reach out and we'll get you set up.</p>
                    <a href="{{ route('public.contact') }}" class="cta inline-block mt-8 px-7 py-3.5 rounded-xl bg-brand-accent text-brand-dark font-bold transition">Contact our team</a>
                </div>
            </div>
        </section>
    </main>

    @include('public.partials.footer')
</body>
</html>
