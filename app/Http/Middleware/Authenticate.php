<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // For API requests, don't redirect
        if ($request->is('api/*')) {
            return null;
        }

        // For platform routes, redirect to platform login
        if ($request->is('platform', 'platform/*')) {
            return route('platform.login');
        }

        // Default: no redirect (will return 401)
        return null;
    }
}
