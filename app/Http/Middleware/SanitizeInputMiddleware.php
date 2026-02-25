<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInputMiddleware
{
    /**
     * Fields that should NOT be sanitized (allow HTML)
     */
    protected array $except = [
        'description',
        'terms',
        'content',
        'bio',
        'about',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value, $key) {
            if (is_string($value) && !in_array($key, $this->except)) {
                // Strip HTML tags from string inputs
                $value = strip_tags($value);
            }
        });

        $request->merge($input);

        return $next($request);
    }
}
