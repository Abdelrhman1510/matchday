<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PlatformAuthController extends Controller
{
    /**
     * Show the platform login form.
     */
    public function showLogin()
    {
        if (Auth::check() && Auth::user()->hasRole('platform_admin')) {
            return redirect()->route('platform.dashboard');
        }

        return view('platform.login');
    }

    /**
     * Handle platform admin login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Check rate limiting
        $key = 'platform-login:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }

        // Attempt authentication
        $credentials = $request->only('email', 'password');
        
        if (!Auth::attempt($credentials, $request->filled('remember'))) {
            RateLimiter::hit($key, 60);
            
            throw ValidationException::withMessages([
                'email' => ['The provided credentials do not match our records.'],
            ]);
        }

        // Check if user has platform_admin role
        if (!Auth::user()->hasRole('platform_admin')) {
            Auth::logout();
            
            throw ValidationException::withMessages([
                'email' => ['You do not have permission to access the platform dashboard.'],
            ]);
        }

        // Clear rate limiter
        RateLimiter::clear($key);

        // Regenerate session
        $request->session()->regenerate();

        return redirect()->intended(route('platform.dashboard'));
    }

    /**
     * Handle platform admin logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform.login');
    }
}
