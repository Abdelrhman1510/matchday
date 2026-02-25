<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        channels: __DIR__ . '/../routes/channels.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global middleware stack
        $middleware->use([
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\RequestLogMiddleware::class,
            \App\Http\Middleware\ApplyPlatformSettings::class,
        ]);

        // API middleware group
        $middleware->group('api', [
            \App\Http\Middleware\SanitizeInputMiddleware::class,
        ]);

        // Trust all proxies for production
        $middleware->trustProxies(at: '*');

        // Register custom middleware aliases
        $middleware->alias([
            'throttle.chat' => \App\Http\Middleware\ThrottleChat::class,
            'cafe.owner' => \App\Http\Middleware\EnsureCafeOwner::class,
            'platform.admin' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
            'subscription' => \App\Http\Middleware\EnforceSubscription::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule) {
        // Expire offers daily at midnight
        $schedule->command('offers:expire')->daily();
        // Check and mark expired subscriptions daily
        $schedule->command('subscriptions:check-expired')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Handle API exceptions - always return JSON
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*');
        });

        // 1. ModelNotFoundException → 404
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'errors' => [],
                ], 404);
            }
        });

        // 2. NotFoundHttpException → 404
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'errors' => [],
                ], 404);
            }
        });

        // 3. MethodNotAllowedHttpException → 405
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed',
                    'errors' => [],
                ], 405);
            }
        });

        // 4. AuthenticationException → 401
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'errors' => [],
                ], 401);
            }
        });

        // 5. AuthorizationException → 403
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is unauthorized',
                    'errors' => [],
                ], 403);
            }
        });

        // 6. ValidationException → 422
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        // 7. ThrottleRequestsException → 429
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? null;

                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please slow down.',
                    'errors' => [],
                    'retry_after' => $retryAfter,
                ], 429);
            }
        });

        // 8. QueryException → 500 (hide SQL details in production)
        $exceptions->render(function (QueryException $e, Request $request) {
            if ($request->is('api/*')) {
                // Log full error with stack trace
                Log::error('Database Query Error', [
                    'message' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'trace' => $e->getTraceAsString(),
                ]);

                $message = app()->environment('production')
                    ? 'Database error occurred. Please try again later.'
                    : $e->getMessage();

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => [],
                ], 500);
            }
        });

        // 9. Generic Exception Handler (catch all 500 errors)
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('api/*')) {
                // Log all 500 errors with full stack trace
                if (!method_exists($e, 'getStatusCode') || $e->getStatusCode() >= 500) {
                    Log::error('API Error', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                        'url' => $request->fullUrl(),
                        'method' => $request->method(),
                        'user_id' => $request->user()?->id,
                    ]);
                }

                $code = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                $message = app()->environment('production') && $code >= 500
                    ? 'An error occurred. Please try again later.'
                    : $e->getMessage();

                return response()->json([
                    'success' => false,
                    'message' => $message ?: 'Server error',
                    'errors' => [],
                ], $code);
            }
        });
    })->create();
