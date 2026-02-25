<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayInterface;
use App\Events\AchievementUnlocked;
use App\Events\BookingCancelled;
use App\Events\BookingCreated;
use App\Events\PointsEarned;
use App\Listeners\SendAchievementUnlockedNotification;
use App\Listeners\SendBookingCancelledNotification;
use App\Listeners\SendBookingConfirmedNotification;
use App\Listeners\SendPointsEarnedNotification;
use App\Services\Payment\SimulatedPaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind payment gateway interface to simulated implementation
        // In production, replace SimulatedPaymentGateway with real gateway (e.g., StripeGateway, TapGateway)
        $this->app->singleton(PaymentGatewayInterface::class, SimulatedPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();
        $this->registerEventListeners();
        $this->registerSqliteFunctions();
    }

    /**
     * Register custom SQLite functions for math operations (needed for Haversine formula).
     */
    protected function registerSqliteFunctions(): void
    {
        try {
            $connection = \Illuminate\Support\Facades\DB::connection();
            if ($connection->getDriverName() === 'sqlite') {
                $pdo = $connection->getPdo();
                $pdo->sqliteCreateFunction('acos', 'acos', 1);
                $pdo->sqliteCreateFunction('cos', 'cos', 1);
                $pdo->sqliteCreateFunction('sin', 'sin', 1);
                $pdo->sqliteCreateFunction('radians', 'deg2rad', 1);
            }
        } catch (\Exception $e) {
            // Silently ignore if DB connection is not available yet
        }
    }

    /**
     * Register event listeners for notifications.
     */
    protected function registerEventListeners(): void
    {
        Event::listen(BookingCreated::class, SendBookingConfirmedNotification::class);
        Event::listen(BookingCancelled::class, SendBookingCancelledNotification::class);
        Event::listen(AchievementUnlocked::class, SendAchievementUnlockedNotification::class);
        Event::listen(PointsEarned::class, SendPointsEarnedNotification::class);
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // Rate limit for authenticated users
        RateLimiter::for('authenticated', function (Request $request) {
            // Extract user ID from token without loading User model
            $token = $request->bearerToken();
            $userId = null;
            
            if ($token) {
                $tokenParts = explode('|', $token);
                if (count($tokenParts) === 2) {
                    $tokenId = $tokenParts[0];
                    $tokenRecord = \DB::table('personal_access_tokens')->where('id', $tokenId)->value('tokenable_id');
                    $userId = $tokenRecord;
                }
            }
            
            return Limit::perMinute(config('app.rate_limit_authenticated', 60))
                ->by($userId ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many requests. Please try again later.',
                        'data' => (object)[],
                        'meta' => (object)[],
                    ], 429, $headers);
                });
        });

        // Rate limit for guest users
        RateLimiter::for('guest', function (Request $request) {
            return Limit::perMinute(config('app.rate_limit_guest', 30))
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many requests. Please try again later.',
                        'data' => (object)[],
                        'meta' => (object)[],
                    ], 429, $headers);
                });
        });

        // Default API rate limiter
        RateLimiter::for('api', function (Request $request) {
            $limit = config('app.rate_limit_guest', 30);

            return Limit::perMinute($limit)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many requests. Please try again later.',
                        'data' => (object)[],
                        'meta' => (object)[],
                    ], 429, $headers);
                });
        });
    }
}
