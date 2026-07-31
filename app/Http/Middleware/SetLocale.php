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
        $locale = $request->header('Accept-Language');

        if ($locale) {
            // Extract primary language (e.g., 'ar' from 'ar-EG')
            $lang = substr($locale, 0, 2);
            
            if (in_array($lang, ['en', 'ar'])) {
                App::setLocale($lang);
            }
        }

        return $next($request);
    }
}
