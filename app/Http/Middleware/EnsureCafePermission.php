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
            $isQrRoute = $request->is('*scan-qr*') || $request->routeIs('*.scan-qr.*');
            $message = $isQrRoute 
                ? 'Please create a cafe first before using the QR Scanner.' 
                : 'No cafe found.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'error_code' => 'NO_CAFE_PROFILE'
            ], 422);
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
