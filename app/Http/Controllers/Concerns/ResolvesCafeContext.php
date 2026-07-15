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

    /**
     * Accessible branch ids for the acting user.
     * Owner = all cafe branches; staff = assigned branches within the cafe;
     * [] when no cafe context resolves (callers guard the no-cafe case with 404 first).
     */
    protected function accessibleBranchIds(Request $request): array
    {
        return $this->cafeContext($request)?->accessibleBranchIds ?? [];
    }

    /**
     * Object-level branch authorization for the acting user.
     *
     * Call AFTER a branch has been confirmed to belong to the acting cafe
     * (e.g. via `$cafe->branches()->findOrFail($id)`), so a missing branch
     * still surfaces as 404. Returns a 403 JsonResponse to short-circuit with
     * when a delegated staff member targets a branch outside their assigned
     * set, or null to proceed. Owners always pass (their accessible set is
     * every cafe branch).
     */
    protected function denyIfBranchInaccessible(Request $request, int $branchId): ?\Illuminate\Http\JsonResponse
    {
        $ctx = $this->cafeContext($request);

        if ($ctx && !$ctx->canAccessBranch($branchId)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        return null;
    }
}
