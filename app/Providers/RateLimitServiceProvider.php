<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // API endpoints: 60/min for authenticated, 30/min for guests
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(60)->by($request->user()->id)
                : Limit::perMinute(30)->by($request->ip());
        });

        // Auth endpoints: 10 requests/min per identifier+IP combination
        RateLimiter::for('auth', function (Request $request) {
            $identifier = $request->input('email_or_phone')
                ?? $request->input('email')
                ?? $request->input('phone')
                ?? '';

            $key = \Illuminate\Support\Str::lower(trim($identifier)) . '|' . $request->ip();

            return Limit::perMinute(10)->by($key);
        });

        // Password reset: 3 requests/min
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinute(3)->by($request->ip());
        });

        // File uploads: 5 requests/min
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(5)->by($request->user()?->id ?? $request->ip());
        });

        // Resend verification: 1/min
        RateLimiter::for('resend-otp', function (Request $request) {
            return Limit::perMinute(1)->by($request->user()?->id ?? $request->ip());
        });
    }
}
