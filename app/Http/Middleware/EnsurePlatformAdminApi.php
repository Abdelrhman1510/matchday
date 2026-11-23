<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformAdminApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->hasRole('platform_admin')) {
            return response()->json([
                'success' => false,
                'message' => __('wallet.errors.admin_only'),
                'errors' => (object) [],
            ], 403);
        }

        return $next($request);
    }
}
