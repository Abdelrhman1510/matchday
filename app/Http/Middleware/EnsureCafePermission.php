<?php

namespace App\Http\Middleware;

use App\Services\CafeContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCafePermission
{
    public function __construct(private CafeContextResolver $resolver) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();
        $ctx = $user ? $this->resolver->resolve($user) : null;

        if (!$ctx) {
            return response()->json(['success' => false, 'message' => 'No cafe found.'], 404);
        }

        // Reuse downstream (controllers read the same context).
        $request->attributes->set('cafe_context', $ctx);

        $allowed = $permission === 'owner' ? $ctx->isOwner : $ctx->can($permission);

        if ($allowed) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to perform this action.',
        ], 403);
    }
}
