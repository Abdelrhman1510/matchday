<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us — Shaja3</title>
    <meta name="description" content="Get in touch with the Shaja3 team.">
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

    <header class="max-w-5xl mx-auto px-6 py-6 flex items-center justify-between">
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <img src="{{ asset('images/shaja3_icon.png') }}" alt="Shaja3" class="w-10 h-10 rounded-xl object-cover">
            <span class="font-bungee text-white text-xl">Shaja3</span>
        </a>
        <a href="{{ url('/') }}" class="text-sm font-semibold text-slate-300 hover:text-brand-accent transition-colors">← Home</a>
    </header>

    <main class="max-w-xl mx-auto px-6 py-12">
        <h1 class="font-bungee text-3xl sm:text-4xl text-white text-center">Contact Us</h1>
        <p class="text-center text-slate-400 mt-3">Questions, feedback, or partnership? Send us a message.</p>

        @if (session('success'))
            <div class="mt-8 rounded-xl border border-brand-accent/40 bg-brand-accent/10 text-brand-accent px-4 py-3 text-sm">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mt-8 rounded-xl border border-red-500/40 bg-red-500/10 text-red-300 px-4 py-3 text-sm">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('public.contact.submit') }}" class="mt-8 space-y-5">
            @csrf
            <!-- honeypot: hidden from humans -->
            <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

            <div>
                <label class="block text-sm text-slate-400 mb-1">Name</label>
                <input name="name" value="{{ old('name') }}" required maxlength="120"
                    class="w-full rounded-xl bg-brand-card border border-brand-border px-4 py-3 text-white focus:border-brand-accent focus:outline-none">
                @error('name') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required maxlength="180"
                    class="w-full rounded-xl bg-brand-card border border-brand-border px-4 py-3 text-white focus:border-brand-accent focus:outline-none">
                @error('email') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">Subject <span class="text-slate-600">(optional)</span></label>
                <input name="subject" value="{{ old('subject') }}" maxlength="150"
                    class="w-full rounded-xl bg-brand-card border border-brand-border px-4 py-3 text-white focus:border-brand-accent focus:outline-none">
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-1">Message</label>
                <textarea name="message" required rows="5" maxlength="3000"
                    class="w-full rounded-xl bg-brand-card border border-brand-border px-4 py-3 text-white focus:border-brand-accent focus:outline-none">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="w-full py-3 rounded-xl bg-brand-accent text-brand-dark font-bold hover:opacity-90 transition">Send message</button>
        </form>
    </main>

    @include('public.partials.footer')
</body>
</html>
