<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ThrottleChat
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'chat-message:' . $request->user()->id;
        
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many messages. Please wait before sending another message.',
                'data' => (object)[],
                'meta' => [
                    'retry_after' => $seconds,
                ],
            ], 429);
        }
        
        RateLimiter::hit($key, 60); // 60 seconds = 1 minute
        
        return $next($request);
    }
}
