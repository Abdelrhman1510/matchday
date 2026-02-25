<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCafeOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage cafe profiles.',
            ], 403);
        }

        // Check by role first
        $isCafeOwner = $user->role === 'cafe_owner' 
            || $user->role === 'admin';

        // Also allow staff members assigned to a branch
        if (!$isCafeOwner) {
            $isStaff = \Illuminate\Support\Facades\DB::table('branch_staff')
                ->where('user_id', $user->id)
                ->exists();
            if ($isStaff) {
                return $next($request);
            }
        }

        if (!$isCafeOwner) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to manage cafe profiles.',
            ], 403);
        }

        return $next($request);
    }
}
