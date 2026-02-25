<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('platform.login');
        }

        if (!Auth::user()->hasRole('platform_admin')) {
            Auth::logout();
            return redirect()->route('platform.login')
                ->withErrors(['access' => 'You do not have permission to access this area.']);
        }

        return $next($request);
    }
}
