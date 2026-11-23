<?php

namespace App\Http\Middleware;

use App\Services\CafeContextResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Wallet and bank APIs are for the cafe owner only, even staff with every permission get 403. */
class EnsureWalletOwner
{
    public function __construct(private CafeContextResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $context = $user ? $this->resolver->resolve($user) : null;

        if (!$context || !$context->isOwner) {
            return response()->json([
                'success' => false,
                'message' => __('wallet.errors.owner_only'),
                'errors' => (object) [],
            ], 403);
        }

        $request->attributes->set('wallet_cafe', $context->cafe);

        return $next($request);
    }
}
