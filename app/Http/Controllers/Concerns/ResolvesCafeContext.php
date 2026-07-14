<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Cafe;
use App\Services\CafeContextResolver;
use App\Support\CafeContext;
use Illuminate\Http\Request;

trait ResolvesCafeContext
{
    protected function cafeContext(Request $request): ?CafeContext
    {
        if ($request->attributes->has('cafe_context')) {
            return $request->attributes->get('cafe_context');
        }

        $ctx = $request->user()
            ? app(CafeContextResolver::class)->resolve($request->user())
            : null;

        $request->attributes->set('cafe_context', $ctx);

        return $ctx;
    }

    protected function actingCafe(Request $request): ?Cafe
    {
        return $this->cafeContext($request)?->cafe;
    }
}
