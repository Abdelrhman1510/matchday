<?php

namespace App\Http\Controllers;

use App\Http\Resources\SavedCafeResource;
use App\Services\SavedCafeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class SavedCafeController extends Controller
{
    protected SavedCafeService $savedCafeService;

    public function __construct(SavedCafeService $savedCafeService)
    {
        $this->savedCafeService = $savedCafeService;
    }

    /**
     * GET /api/v1/saved-cafes
     * Get user's saved cafes (requires auth)
     */
    public function index(Request $request): JsonResponse
    {
        $savedCafes = $this->savedCafeService->getSavedCafes($request->user()->id);

        return response()->json([
            'success' => true,
            'message' => 'Saved cafes retrieved',
            'data' => SavedCafeResource::collection($savedCafes),
            'meta' => [
                'total' => $savedCafes->count(),
            ],
        ]);
    }

    /**
     * POST /api/v1/saved-cafes/{cafeId}
     * Save a cafe (requires auth)
     */
    public function store(Request $request, int $cafeId): JsonResponse
    {
        $user = $request->user();
        $result = $this->savedCafeService->saveCafe($user->id, $cafeId);
        Cache::forget("explore_data_user_{$user->id}_no_location");

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['message'] === 'Cafe not found' ? 404 : 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ], 200);
    }

    /**
     * DELETE /api/v1/saved-cafes/{cafeId}
     * Unsave a cafe (requires auth)
     */
    public function destroy(Request $request, int $cafeId): JsonResponse
    {
        $user = $request->user();
        Cache::forget("explore_data_user_{$user->id}_no_location");
        $result = $this->savedCafeService->unsaveCafe($user->id, $cafeId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['message'] === 'Cafe not found' ? 404 : 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }
}
