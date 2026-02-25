<?php

namespace App\Http\Middleware;

use App\Services\CurrencyService;
use App\Services\PlatformSettingsService;
use Closure;
use Illuminate\Http\Request;

class ApplyPlatformSettings
{
    public function handle(Request $request, Closure $next): mixed
    {
        try {
            $settings = app(PlatformSettingsService::class);

            // ── Timezone ──────────────────────────────────────────────────────
            $timezone = $settings->get('timezone', config('app.timezone', 'UTC'));
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);

            // ── Locale / Language ─────────────────────────────────────────────
            $language = $settings->get('platform_language', config('app.locale', 'en'));
            app()->setLocale($language);

            // ── User Currency ─────────────────────────────────────────────────
            // Resolve once per request and attach to request attributes so any
            // controller can do: $request->attributes->get('user_currency')
            $currency = app(CurrencyService::class)->getUserCurrency($request);
            $request->attributes->set('user_currency', $currency);

        } catch (\Throwable) {
            // DB not ready (first install, migrations running) — skip silently
        }

        return $next($request);
    }
}
