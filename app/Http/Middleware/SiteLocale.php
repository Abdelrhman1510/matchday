<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Public marketing/legal pages language toggle. `?lang=ar|en` is remembered in
 * the session so the choice sticks while browsing; defaults to English.
 */
class SiteLocale
{
    public function handle(Request $request, Closure $next)
    {
        $lang = $request->query('lang');
        if (in_array($lang, ['en', 'ar'], true)) {
            $request->session()->put('site_locale', $lang);
        }

        $locale = $request->session()->get('site_locale', 'en');
        App::setLocale(in_array($locale, ['en', 'ar'], true) ? $locale : 'en');

        return $next($request);
    }
}
