<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>Platform Login - MatchDay</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
    <div class="min-h-screen bg-gradient-to-br from-[#0f172a] via-[#0c1425] to-[#0f172a] flex items-center justify-center p-6 relative overflow-hidden">
        <!-- Decorative blurred circles -->
        <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-[#c8ff00]/10 rounded-full blur-3xl"></div>

        <!-- Login Card -->
        <div class="w-full max-w-md relative z-10">
            <div class="bg-[#1e293b] border border-slate-700 rounded-2xl shadow-2xl p-8">
                <!-- Header -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-[#c8ff00]/10 rounded-2xl mb-4">
                        <span class="text-4xl">⚽</span>
                    </div>
                    <h1 class="text-2xl font-bold text-white uppercase tracking-wide mb-2">PLATFORM LOGIN</h1>
                    <p class="text-slate-400 text-sm">Access your platform dashboard</p>
                </div>

                <!-- Error Messages -->
                @if ($errors->any())
                    <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg">
                        @foreach ($errors->all() as $error)
                            <p class="text-red-400 text-sm">{{ $error }}</p>
                        @endforeach
                    </div>
                @endif

                <!-- Login Form -->
                <form method="POST" action="{{ route('platform.login') }}" class="space-y-6">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}"
                            required 
                            autofocus
                            autocomplete="email"
                            class="w-full px-4 py-3 bg-[#0f172a] border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-[#c8ff00] focus:ring-2 focus:ring-[#c8ff00]/20 transition-all"
                            placeholder="admin@matchday.app"
                        >
                    </div>

                    <!-- Password -->
                    <div x-data="{ show: false }">
                        <label for="password" class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                        <div class="relative">
                            <input 
                                :type="show ? 'text' : 'password'" 
                                id="password" 
                                name="password" 
                                required
                                autocomplete="current-password"
                                class="w-full px-4 py-3 bg-[#0f172a] border border-slate-600 rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-[#c8ff00] focus:ring-2 focus:ring-[#c8ff00]/20 transition-all pr-11"
                                placeholder="Enter your password"
                            >
                            <button 
                                type="button"
                                @click="show = !show"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white transition-colors"
                            >
                                <svg x-show="!show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg x-show="show" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                class="w-4 h-4 bg-[#0f172a] border-slate-600 rounded text-[#c8ff00] focus:ring-[#c8ff00] focus:ring-offset-0"
                            >
                            <span class="ml-2 text-sm text-slate-400">Remember me</span>
                        </label>
                        <a href="#" class="text-sm text-[#c8ff00] hover:text-[#d4ff33] transition-colors">
                            Forgot password?
                        </a>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit"
                        class="w-full px-6 py-3 bg-[#c8ff00] hover:bg-[#d4ff33] text-black font-bold uppercase tracking-wide rounded-lg transition-all transform hover:scale-105 active:scale-95 shadow-lg shadow-[#c8ff00]/20"
                    >
                        Login to Dashboard
                    </button>
                </form>

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t border-slate-700">
                    <p class="text-center text-xs text-slate-500 uppercase tracking-wider">
                        🔒 Secured by Football Cafe Platform
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
