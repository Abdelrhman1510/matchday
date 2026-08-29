<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shaja3 — Book your seat, feel the match</title>
    <meta name="description" content="Shaja3 lets fans reserve seats at cafés to watch live football matches together — pick your match, choose your seat, and enjoy the game.">
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
    <style>body{background-color:#0c0628;font-family:'Instrument Sans',sans-serif}</style>
</head>
<body class="text-slate-200">

    <!-- Nav -->
    <header class="max-w-5xl mx-auto px-6 py-6 flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <img src="{{ asset('images/shaja3_icon.png') }}" alt="Shaja3" class="w-10 h-10 rounded-xl object-cover">
            <span class="font-bungee text-white text-xl">Shaja3</span>
        </a>
        <a href="{{ route('public.contact') }}" class="text-sm font-semibold text-slate-300 hover:text-brand-accent transition-colors">Contact</a>
    </header>

    <!-- Hero -->
    <main class="max-w-5xl mx-auto px-6">
        <section class="text-center py-16 sm:py-24">
            <span class="inline-block px-3 py-1 rounded-full border border-brand-border text-brand-accent text-xs font-bold uppercase tracking-wider mb-6">Watch football, together</span>
            <h1 class="font-bungee text-4xl sm:text-6xl text-white leading-tight">Book your seat.<br><span class="text-brand-accent">Feel the match.</span></h1>
            <p class="max-w-xl mx-auto mt-6 text-lg text-slate-400">Reserve a spot at your favourite café to watch live matches on the big screen. Pick the game, choose your seat, and show up ready to cheer.</p>
            <div class="mt-10 flex items-center justify-center gap-4">
                <a href="{{ route('public.contact') }}" class="px-6 py-3 rounded-xl bg-brand-accent text-brand-dark font-bold hover:opacity-90 transition">Get in touch</a>
                <a href="#features" class="px-6 py-3 rounded-xl border border-brand-border text-white font-semibold hover:border-slate-500 transition">How it works</a>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 pb-8">
            @foreach ([
                ['⚽', 'Live matches', 'Browse upcoming games and see which cafés are showing them.'],
                ['🎟️', 'Reserve seats', 'Choose your section and seats, then confirm with a QR code for entry.'],
                ['🏆', 'Loyalty rewards', 'Earn points on every booking and climb from Bronze to Platinum.'],
                ['💬', 'Fan Room', 'Chat live with other fans watching the same match at your venue.'],
            ] as [$icon, $title, $desc])
                <div class="bg-brand-card border border-brand-border rounded-2xl p-6">
                    <div class="text-3xl mb-3">{{ $icon }}</div>
                    <h3 class="font-bold text-white mb-1">{{ $title }}</h3>
                    <p class="text-sm text-slate-400">{{ $desc }}</p>
                </div>
            @endforeach
        </section>

        <!-- CTA -->
        <section class="my-16 bg-brand-card border border-brand-border rounded-3xl px-8 py-12 text-center">
            <h2 class="font-bungee text-2xl sm:text-3xl text-white">Own a café?</h2>
            <p class="max-w-lg mx-auto mt-3 text-slate-400">List your branches, publish the matches you're showing, and fill your seats. Reach out and we'll get you set up.</p>
            <a href="{{ route('public.contact') }}" class="inline-block mt-8 px-6 py-3 rounded-xl bg-brand-accent text-brand-dark font-bold hover:opacity-90 transition">Contact our team</a>
        </section>
    </main>

    @include('public.partials.footer')
</body>
</html>
