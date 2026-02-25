@php
    // Apply saved locale on every platform page load
    $platformLocale = session(
        'platform_locale',
        app(\App\Services\PlatformSettingsService::class)->get('platform_language', 'en')
    );
    $platformLocale = in_array($platformLocale, ['en', 'ar']) ? $platformLocale : 'en';
    app()->setLocale($platformLocale);
    $isRtl = $platformLocale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $platformLocale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} - FootCafe Platform</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&family=Bungee&family=Cairo:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">


    @livewireScriptConfig
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <style>
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.2s ease-out;
        }

        .font-bungee {
            font-family: 'Bungee', cursive;
        }

        .bg-custom-dark {
            background-color: #07021e;
        }

        .bg-custom-main {
            background-color: #0c0628;
        }

        .border-custom {
            border-color: #1e164e;
        }

        /* Arabic font override */
        [lang="ar"],
        [lang="ar"] * {
            font-family: 'Cairo', sans-serif !important;
        }

        [lang="ar"] .font-bungee {
            font-family: 'Cairo', sans-serif !important;
            letter-spacing: 0 !important;
        }

        /* Flip icon for RTL close button */
        .icon-flip-rtl {
            transform: scaleX(-1);
        }

        /* Table header alignment — override browser default center for th */
        table th {
            text-align: left;
        }

        [dir="rtl"] table th {
            text-align: right;
        }
    </style>
</head>

<body class="antialiased">
    <div class="flex min-h-screen bg-custom-main" x-data="{
            sidebarOpen: window.innerWidth >= 1024,
            isMobile: window.innerWidth < 1024,
            isRtl: {{ $isRtl ? 'true' : 'false' }}
        }" @resize.window="
            isMobile = window.innerWidth < 1024;
            if (!isMobile) sidebarOpen = true;
        ">
        <!-- Sidebar -->
        <aside
            :style="sidebarOpen ? 'transform: translateX(0)' : (isRtl ? 'transform: translateX(100%)' : 'transform: translateX(-100%)')"
            class="w-60 fixed h-screen bg-custom-dark {{ $isRtl ? 'border-l right-0' : 'border-r left-0' }} border-custom flex flex-col z-50 transition-transform duration-300 ease-in-out">
            <!-- Logo -->
            <div class="p-6 border-b border-custom">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex items-center justify-center w-8 h-8 bg-[#c8ff00] text-black rounded font-black text-xl leading-none">
                            ⚽
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-white font-bungee leading-none mt-1">FOOTCAFE</h1>
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider mt-0.5">
                                {{ __('platform.nav.admin_panel') }}
                            </p>
                        </div>
                    </div>
                    <!-- Close sidebar button -->
                    <button @click="sidebarOpen = false"
                        class="w-7 h-7 flex items-center justify-center rounded text-slate-400 hover:text-white hover:bg-[#1a0e40] transition-colors"
                        title="Close sidebar">
                        <svg class="w-4 h-4 {{ $isRtl ? 'icon-flip-rtl' : '' }}" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                <x-platform.nav-link href="{{ route('platform.dashboard') }}"
                    :active="request()->routeIs('platform.dashboard')" icon="chart-bar">
                    {{ __('platform.nav.overview') }}
                </x-platform.nav-link>

                <x-platform.nav-link href="{{ route('platform.cafes') }}"
                    :active="request()->routeIs('platform.cafes*')" icon="building">
                    {{ __('platform.nav.cafes') }}
                </x-platform.nav-link>

                <x-platform.nav-link href="{{ route('platform.matches') }}"
                    :active="request()->routeIs('platform.matches')" icon="trophy">
                    {{ __('platform.nav.matches') }}
                </x-platform.nav-link>

                <x-platform.nav-link href="{{ route('platform.users') }}" :active="request()->routeIs('platform.users')"
                    icon="users">
                    {{ __('platform.nav.customers') }}
                </x-platform.nav-link>

                <x-platform.nav-link href="{{ route('platform.subscriptions') }}"
                    :active="request()->routeIs('platform.subscriptions')" icon="credit-card">
                    {{ __('platform.nav.subscriptions') }}
                </x-platform.nav-link>

                <x-platform.nav-link href="{{ route('platform.plans') }}" :active="request()->routeIs('platform.plans')"
                    icon="star">
                    {{ __('platform.nav.plans') }}
                </x-platform.nav-link>

                <x-platform.nav-link href="{{ route('platform.reports') }}"
                    :active="request()->routeIs('platform.reports')" icon="document">
                    {{ __('platform.nav.reports') }}
                </x-platform.nav-link>

                <div class="pt-4 mt-4 border-t border-custom">
                    <x-platform.nav-link href="{{ route('platform.settings') }}"
                        :active="request()->routeIs('platform.settings')" icon="cog">
                        {{ __('platform.nav.settings') }}
                    </x-platform.nav-link>
                </div>
            </nav>

            <!-- User Profile -->
            <div class="p-4 border-t border-custom">
                <div class="flex items-center gap-3">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin User') }}&background=c8ff00&color=000&size=100"
                        class="w-10 h-10 rounded-full" alt="Profile">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                        <p class="text-xs text-slate-400 uppercase tracking-wide">
                            {{ __('platform.nav.platform_owner') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('platform.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-white transition-colors" title="Logout">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                </path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Mobile backdrop overlay -->
        <div x-show="sidebarOpen && isMobile" x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-200" x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-black/50"
            style="display: none;"></div>

        <!-- Open sidebar button (visible when sidebar is hidden) -->
        <button x-show="!sidebarOpen" @click="sidebarOpen = true"
            class="fixed top-5 {{ $isRtl ? 'right-4' : 'left-4' }} z-50 w-10 h-10 flex items-center justify-center bg-[#0e0735] border border-[#1e164e] rounded-lg text-slate-400 hover:text-white hover:bg-[#1a0e40] transition-all duration-200 shadow-lg"
            title="Open sidebar" style="display: none;">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        <!-- Main Content -->
        <main :style="sidebarOpen && !isMobile ? '{{ $isRtl ? 'padding-right' : 'padding-left' }}: 240px' : ''"
            class="flex-1 min-w-0 transition-all duration-300 ease-in-out">
            <div class="p-4 md:p-8">
                {{ $slot }}
            </div>
        </main>
    </div>

    @stack('scripts')

    <script>
        // Reload page when language is switched so server-rendered translations refresh
        document.addEventListener('livewire:init', () => {
            Livewire.on('localeChanged', () => {
                window.location.reload();
            });
        });
    </script>
</body>

</html>