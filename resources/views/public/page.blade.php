<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }} - TAB3</title>
    <meta name="description" content="{{ $page->title }} - TAB3 App">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Bungee&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-dark':  '#07021e',
                        'brand-main':  '#0c0628',
                        'brand-card':  '#110830',
                        'brand-border':'#1e164e',
                        'brand-accent':'#c8ff00',
                    },
                    fontFamily: {
                        sans:   ['Instrument Sans', 'sans-serif'],
                        bungee: ['Bungee', 'cursive'],
                    },
                }
            }
        }
    </script>
    <style>
        body { background-color: #0c0628; font-family: 'Instrument Sans', sans-serif; }

        /* Prose styles for the HTML content from the database */
        .page-content h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #1e164e;
        }
        .page-content h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #c8ff00;
            margin-top: 2rem;
            margin-bottom: 0.75rem;
        }
        .page-content h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #e2e8f0;
            margin-top: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .page-content p {
            color: #94a3b8;
            line-height: 1.75;
            margin-bottom: 1rem;
        }
        .page-content ul {
            list-style: none;
            padding: 0;
            margin-bottom: 1rem;
        }
        .page-content ul li {
            color: #94a3b8;
            line-height: 1.75;
            padding: 0.375rem 0 0.375rem 1.5rem;
            position: relative;
        }
        .page-content ul li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            background-color: #c8ff00;
            border-radius: 50%;
        }
        .page-content strong {
            color: #e2e8f0;
            font-weight: 600;
        }
        .page-content a {
            color: #c8ff00;
            text-decoration: underline;
        }
        .page-content a:hover {
            color: #ffffff;
        }
    </style>
</head>
<body class="min-h-screen antialiased">

    <!-- Header -->
    <header class="bg-brand-dark border-b border-brand-border sticky top-0 z-50">
        <div class="max-w-4xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <!-- Logo -->
                <a href="/" class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-9 h-9 bg-brand-accent text-black rounded-lg font-black text-xl leading-none">
                        ⚽
                    </div>
                    <span class="text-2xl font-bungee text-white tracking-wide">TAB3</span>
                </a>

                <!-- Back to app nudge -->
                <a href="#" class="hidden sm:inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Download the App
                </a>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <div class="bg-brand-dark border-b border-brand-border">
        <div class="max-w-4xl mx-auto px-6 py-10">
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-3">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Legal</span>
                <span>&rsaquo;</span>
                <span class="text-slate-400">{{ $page->title }}</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-white">{{ $page->title }}</h1>
            <div class="mt-2 flex items-center gap-2">
                <span class="inline-block w-8 h-0.5 bg-brand-accent rounded"></span>
                <span class="text-sm text-slate-500">TAB3 App</span>
            </div>
        </div>
    </div>

    <!-- Content -->
    <main class="max-w-4xl mx-auto px-6 py-10">
        <div class="bg-brand-card border border-brand-border rounded-2xl p-8 md:p-10">
            <div class="page-content">
                {!! $page->content !!}
            </div>
        </div>

        <!-- Related pages -->
        <div class="mt-8 pt-6 border-t border-brand-border">
            <p class="text-sm text-slate-500 mb-4">Related pages</p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('public.privacy-policy') }}"
                   class="px-4 py-2 rounded-lg border text-sm transition-colors
                          {{ request()->routeIs('public.privacy-policy')
                              ? 'border-brand-accent text-brand-accent bg-brand-accent/10'
                              : 'border-brand-border text-slate-400 hover:text-white hover:border-slate-500' }}">
                    Privacy Policy
                </a>
                <a href="{{ route('public.pages', 'terms-and-conditions') }}"
                   class="px-4 py-2 rounded-lg border text-sm transition-colors
                          {{ request()->is('pages/terms-and-conditions')
                              ? 'border-brand-accent text-brand-accent bg-brand-accent/10'
                              : 'border-brand-border text-slate-400 hover:text-white hover:border-slate-500' }}">
                    Terms &amp; Conditions
                </a>
                <a href="{{ route('public.pages', 'usage-policy') }}"
                   class="px-4 py-2 rounded-lg border text-sm transition-colors
                          {{ request()->is('pages/usage-policy')
                              ? 'border-brand-accent text-brand-accent bg-brand-accent/10'
                              : 'border-brand-border text-slate-400 hover:text-white hover:border-slate-500' }}">
                    Usage Policy
                </a>
                <a href="{{ route('public.pages', 'cookie-policy') }}"
                   class="px-4 py-2 rounded-lg border text-sm transition-colors
                          {{ request()->is('pages/cookie-policy')
                              ? 'border-brand-accent text-brand-accent bg-brand-accent/10'
                              : 'border-brand-border text-slate-400 hover:text-white hover:border-slate-500' }}">
                    Cookie Policy
                </a>
                <a href="{{ route('public.pages', 'faq') }}"
                   class="px-4 py-2 rounded-lg border text-sm transition-colors
                          {{ request()->is('pages/faq')
                              ? 'border-brand-accent text-brand-accent bg-brand-accent/10'
                              : 'border-brand-border text-slate-400 hover:text-white hover:border-slate-500' }}">
                    FAQ
                </a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-brand-dark border-t border-brand-border mt-16">
        <div class="max-w-4xl mx-auto px-6 py-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center w-7 h-7 bg-brand-accent text-black rounded font-black text-sm">
                        ⚽
                    </div>
                    <span class="font-bungee text-white">TAB3</span>
                </div>
                <p class="text-sm text-slate-500">&copy; {{ date('Y') }} TAB3. All rights reserved.</p>
                <div class="flex items-center gap-4 text-sm text-slate-500">
                    <a href="{{ route('public.privacy-policy') }}" class="hover:text-white transition-colors">Privacy</a>
                    <a href="{{ route('public.pages', 'terms-and-conditions') }}" class="hover:text-white transition-colors">Terms</a>
                    <a href="{{ route('public.pages', 'faq') }}" class="hover:text-white transition-colors">FAQ</a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
