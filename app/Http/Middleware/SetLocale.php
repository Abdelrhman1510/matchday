<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $lang = null;

        // 1) The authenticated user's saved locale preference is the most reliable
        //    signal for the mobile app (the device's Accept-Language often doesn't
        //    match the in-app language). Resolve straight from the Sanctum bearer
        //    token — this runs as global middleware, before route auth:sanctum.
        if ($request->bearerToken()) {
            $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($request->bearerToken());
            $userLocale = optional(optional($accessToken)->tokenable)->locale;
            if (in_array($userLocale, ['en', 'ar'], true)) {
                $lang = $userLocale;
            }
        }

        // 2) Otherwise use the Accept-Language header (e.g. 'ar' from 'ar-EG').
        //    ONLY for API requests. Web requests (the platform dashboard) take
        //    their locale from the platform_language setting via
        //    ApplyPlatformSettings; letting the browser's Accept-Language
        //    override it makes Livewire XHR requests — which resolve the header
        //    differently than the top-level navigation — render the lazy-loaded
        //    content in a different language than the surrounding page.
        if ($lang === null && $request->is('api/*')) {
            $header = $request->header('Accept-Language');
            if ($header) {
                $candidate = substr($header, 0, 2);
                if (in_array($candidate, ['en', 'ar'], true)) {
                    $lang = $candidate;
                }
            }
        }

        if (in_array($lang, ['en', 'ar'], true)) {
            App::setLocale($lang);
        }

        return $next($request);
    }
}
