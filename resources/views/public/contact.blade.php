<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — Shaj3</title>
    <meta name="description" content="Get in touch with the Shaj3 team.">
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
        .glow::before {
            content:""; position:absolute; inset:-20% 0 auto 0; height:420px; z-index:0;
            background: radial-gradient(500px 260px at 50% 0%, rgba(200,255,0,.12), transparent 70%);
            pointer-events:none;
        }
        .field { transition: border-color .15s ease, box-shadow .15s ease; }
        .field:focus { border-color:#c8ff00; box-shadow:0 0 0 3px rgba(200,255,0,.12); outline:none; }
        .cta { box-shadow:0 10px 30px -8px rgba(200,255,0,.35); }
        .cta:hover { box-shadow:0 14px 36px -8px rgba(200,255,0,.5); }
    </style>
</head>
<body class="text-slate-200">

    <header class="sticky top-0 z-30 backdrop-blur-md bg-brand-main/70 border-b border-brand-border/60">
        <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-3">
                <img src="{{ asset('images/shaja3_icon.png') }}" alt="Shaj3" class="w-9 h-9 rounded-lg object-cover">
                <span class="font-bungee text-white text-lg tracking-wide">Shaj3</span>
            </a>
            <a href="{{ url('/') }}" class="text-sm font-semibold text-slate-300 hover:text-white transition-colors">← Home</a>
        </div>
    </header>

    <main class="glow relative">
        <div class="relative z-10 max-w-xl mx-auto px-6 pt-16 pb-12">
            <div class="text-center">
                <span class="inline-block px-3 py-1 rounded-full border border-brand-border text-brand-accent text-xs font-bold uppercase tracking-widest">We're listening</span>
                <h1 class="font-bungee text-3xl sm:text-5xl text-white mt-5">Contact Us</h1>
                <p class="text-slate-400 mt-3">Questions, feedback, or partnership? Send us a message and we'll get back to you.</p>
            </div>

            @if (session('success'))
                <div class="mt-8 rounded-xl border border-brand-accent/40 bg-brand-accent/10 text-brand-accent px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="mt-8 rounded-xl border border-red-500/40 bg-red-500/10 text-red-300 px-4 py-3 text-sm">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('public.contact.submit') }}"
                  class="mt-8 bg-brand-card/70 border border-brand-border rounded-2xl p-6 sm:p-8 space-y-5 backdrop-blur">
                @csrf
                <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                <div class="grid sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">Name</label>
                        <input name="name" value="{{ old('name') }}" required maxlength="120"
                            class="field w-full rounded-xl bg-brand-dark/60 border border-brand-border px-4 py-3 text-white placeholder-slate-600" placeholder="Your name">
                        @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-1.5">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required maxlength="180"
                            class="field w-full rounded-xl bg-brand-dark/60 border border-brand-border px-4 py-3 text-white placeholder-slate-600" placeholder="you@example.com">
                        @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Subject <span class="text-slate-600 font-normal">(optional)</span></label>
                    <input name="subject" value="{{ old('subject') }}" maxlength="150"
                        class="field w-full rounded-xl bg-brand-dark/60 border border-brand-border px-4 py-3 text-white placeholder-slate-600" placeholder="What's this about?">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-1.5">Message</label>
                    <textarea name="message" required rows="5" maxlength="3000"
                        class="field w-full rounded-xl bg-brand-dark/60 border border-brand-border px-4 py-3 text-white placeholder-slate-600" placeholder="Tell us more…">{{ old('message') }}</textarea>
                    @error('message') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="cta w-full py-3.5 rounded-xl bg-brand-accent text-brand-dark font-bold transition">Send message</button>
            </form>
        </div>
    </main>

    @include('public.partials.footer')
</body>
</html>
