<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>How to Delete Your Account - TAB3</title>
    <meta name="description" content="Step-by-step guide to delete your TAB3 account permanently.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Bungee&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-dark':   '#07021e',
                        'brand-main':   '#0c0628',
                        'brand-card':   '#110830',
                        'brand-border': '#1e164e',
                        'brand-accent': '#c8ff00',
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

        /* Phone frame wrapper */
        .phone-frame {
            position: relative;
            border-radius: 2.5rem;
            border: 2px solid #1e164e;
            overflow: hidden;
            box-shadow: 0 0 0 6px #07021e, 0 25px 60px rgba(0,0,0,0.6), 0 0 40px rgba(200, 255, 0, 0.06);
            background: #07021e;
            max-width: 220px;
            margin: 0 auto;
        }
        .phone-frame img {
            display: block;
            width: 100%;
            height: auto;
        }

        /* Step connector line */
        .step-connector {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            width: 2px;
            height: 100%;
            background: linear-gradient(to bottom, #1e164e, #c8ff00, #1e164e);
            top: 0;
            z-index: 0;
        }

        /* Warning box */
        .warning-box {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 1rem;
        }
    </style>
</head>
<body class="min-h-screen antialiased">

    <!-- Header -->
    <header class="bg-brand-dark border-b border-brand-border sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <a href="/" class="flex items-center gap-3">
                    <img src="{{ asset('images/tab3_icon.png') }}" alt="TAB3" class="w-9 h-9 rounded-lg object-cover">
                    <span class="text-2xl font-bungee text-white tracking-wide">TAB3</span>
                </a>
                <a href="#" class="hidden sm:inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Download the App
                </a>
            </div>
        </div>
    </header>

    <!-- Hero -->
    <div class="bg-brand-dark border-b border-brand-border">
        <div class="max-w-5xl mx-auto px-6 py-12">
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-4">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Account</span>
                <span>&rsaquo;</span>
                <span class="text-slate-400">Delete Account</span>
            </div>

            <h1 class="text-3xl md:text-4xl font-bold text-white">How to Delete Your Account</h1>
            <div class="mt-2 flex items-center gap-2 mb-6">
                <span class="inline-block w-8 h-0.5 bg-brand-accent rounded"></span>
                <span class="text-sm text-slate-500">TAB3 App · 3 simple steps</span>
            </div>
            <p class="text-slate-400 max-w-2xl leading-relaxed">
                You can permanently delete your TAB3 account directly from the app at any time.
                Follow the steps below to remove your account and all associated data.
            </p>
        </div>
    </div>

    <main class="max-w-5xl mx-auto px-6 py-12 space-y-16">

        <!-- Warning Banner -->
        <div class="warning-box p-5 flex gap-4">
            <div class="shrink-0 mt-0.5">
                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-red-400 mb-1">This action is permanent and cannot be undone</p>
                <p class="text-sm text-slate-400 leading-relaxed">
                    Deleting your account will permanently remove your profile, booking history, loyalty points, and all personal data from TAB3.
                    If you are a café owner with active bookings, your account cannot be deleted until all bookings are completed or cancelled.
                </p>
            </div>
        </div>

        <!-- Steps -->
        <div class="space-y-20">

            <!-- Step 1 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                <!-- Text -->
                <div class="order-2 md:order-1">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center justify-center w-9 h-9 rounded-full bg-brand-accent text-black font-bold text-base shrink-0">
                            1
                        </div>
                        <h2 class="text-xl font-bold text-white">Open your Profile</h2>
                    </div>
                    <p class="text-slate-400 leading-relaxed mb-4">
                        Tap the <span class="text-white font-medium">Profile</span> icon at the bottom of the navigation bar to open your profile page.
                        Then scroll down and tap <span class="text-white font-medium">"Privacy &amp; Terms"</span>.
                    </p>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            Tap the <strong class="text-slate-300 mx-1">Profile</strong> tab (bottom right)
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            Scroll down to find <strong class="text-slate-300 mx-1">Privacy &amp; Terms</strong>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            Tap it to open the legal information page
                        </div>
                    </div>
                </div>
                <!-- Screenshot -->
                <div class="order-1 md:order-2">
                    <div class="phone-frame">
                        <img src="{{ asset('images/account-deletion/step-1.png') }}" alt="Step 1: Profile screen - tap Privacy &amp; Terms">
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="flex items-center gap-4">
                <div class="flex-1 h-px bg-brand-border"></div>
                <div class="flex items-center gap-1.5 text-xs text-slate-600 uppercase tracking-widest">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    next step
                </div>
                <div class="flex-1 h-px bg-brand-border"></div>
            </div>

            <!-- Step 2 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                <!-- Screenshot -->
                <div>
                    <div class="phone-frame">
                        <img src="{{ asset('images/account-deletion/step-2.png') }}" alt="Step 2: Privacy &amp; Terms - tap Delete Account">
                    </div>
                </div>
                <!-- Text -->
                <div>
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center justify-center w-9 h-9 rounded-full bg-brand-accent text-black font-bold text-base shrink-0">
                            2
                        </div>
                        <h2 class="text-xl font-bold text-white">Tap "Delete Account"</h2>
                    </div>
                    <p class="text-slate-400 leading-relaxed mb-4">
                        On the <span class="text-white font-medium">Privacy &amp; Terms</span> screen, scroll to the bottom and tap the
                        <span class="text-red-400 font-medium">"Delete Account"</span> option highlighted in red.
                    </p>
                    <div class="flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            You'll see links to Privacy Policy, Terms &amp; Conditions, Data Usage, and Cookie Policy
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            At the bottom, tap the red <strong class="text-red-400 mx-1">Delete Account</strong> button
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-brand-accent shrink-0"></span>
                            A confirmation dialog will appear
                        </div>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="flex items-center gap-4">
                <div class="flex-1 h-px bg-brand-border"></div>
                <div class="flex items-center gap-1.5 text-xs text-slate-600 uppercase tracking-widest">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                    next step
                </div>
                <div class="flex-1 h-px bg-brand-border"></div>
            </div>

            <!-- Step 3 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center">
                <!-- Text -->
                <div class="order-2 md:order-1">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center justify-center w-9 h-9 rounded-full bg-red-500 text-white font-bold text-base shrink-0">
                            3
                        </div>
                        <h2 class="text-xl font-bold text-white">Confirm Deletion</h2>
                    </div>
                    <p class="text-slate-400 leading-relaxed mb-4">
                        A confirmation dialog will appear warning you that <span class="text-white font-medium">this action is permanent and cannot be undone</span>.
                        Tap <span class="text-red-400 font-medium">"Yes, Delete"</span> to permanently delete your account.
                    </p>
                    <div class="flex flex-col gap-2 mb-5">
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            Read the warning carefully — all your data will be lost
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            Tap <strong class="text-slate-300 mx-1">Cancel</strong> to go back safely
                        </div>
                        <div class="flex items-center gap-2 text-sm text-slate-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>
                            Tap <strong class="text-red-400 mx-1">Yes, Delete</strong> to confirm permanently
                        </div>
                    </div>

                    <!-- What gets deleted -->
                    <div class="bg-brand-card border border-brand-border rounded-xl p-4">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-3">What gets deleted</p>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm text-slate-400">
                                <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Your profile and personal information
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-400">
                                <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Booking history
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-400">
                                <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Loyalty points &amp; tier status
                            </div>
                            <div class="flex items-center gap-2 text-sm text-slate-400">
                                <svg class="w-4 h-4 text-red-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Achievements &amp; preferences
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Screenshot -->
                <div class="order-1 md:order-2">
                    <div class="phone-frame">
                        <img src="{{ asset('images/account-deletion/step-3.png') }}" alt="Step 3: Confirm account deletion dialog">
                    </div>
                </div>
            </div>

        </div>

        <!-- Note for cafe owners -->
        <div class="bg-brand-card border border-brand-border rounded-2xl p-6">
            <div class="flex gap-4">
                <div class="shrink-0">
                    <div class="w-10 h-10 rounded-full bg-brand-accent/10 border border-brand-accent/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-brand-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <h3 class="font-semibold text-white mb-2">Note for Café Owners</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        If you are registered as a café owner, your account <strong class="text-white">cannot be deleted while you have active bookings</strong>
                        (pending, confirmed, or checked-in). You must wait for all bookings to complete or cancel them first before deleting your account.
                    </p>
                </div>
            </div>
        </div>

        <!-- Need help? -->
        <div class="text-center py-4">
            <p class="text-slate-500 text-sm mb-2">Need help or having trouble deleting your account?</p>
            <a href="mailto:support@tab3.app" class="inline-flex items-center gap-2 text-brand-accent hover:text-white transition-colors font-medium text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                Contact us at support@tab3.app
            </a>
        </div>

    </main>

    <!-- Footer -->
    <footer class="bg-brand-dark border-t border-brand-border mt-8">
        <div class="max-w-5xl mx-auto px-6 py-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/tab3_icon.png') }}" alt="TAB3" class="w-7 h-7 rounded object-cover">
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
