<?php

namespace App\Http\Controllers;

use App\Http\Resources\CafeResource;
use App\Http\Resources\CafeDetailResource;
use App\Http\Resources\BranchResource;
use App\Services\CafeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CafeController extends Controller
{
    protected CafeService $cafeService;

    public function __construct(CafeService $cafeService)
    {
        $this->cafeService = $cafeService;
    }

    /**
     * GET /api/v1/cafes
     * List cafes with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $featured = $request->has('featured') ? $request->boolean('featured') : null;
        $city = $request->input('city');
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        $cafes = $this->cafeService->getCafes(
            $featured,
            $city,
            $search,
            $perPage
        );

        return response()->json([
            'success' => true,
            'message' => 'Cafes retrieved successfully',
            'data' => [
                'data' => CafeResource::collection($cafes->items()),
                'current_page' => $cafes->currentPage(),
                'last_page' => $cafes->lastPage(),
                'per_page' => $cafes->perPage(),
                'total' => $cafes->total(),
            ],
        ]);
    }

    /**
     * GET /api/v1/cafes/search
     * Search cafes by query and optional city
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'city' => 'nullable|string',
        ]);

        $cafes = $this->cafeService->searchCafes(
            $request->input('q'),
            $request->input('city')
        );

        return response()->json([
            'success' => true,
            'message' => 'Search results retrieved successfully',
            'data' => CafeResource::collection($cafes),
            'meta' => [
                'total' => $cafes->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/cafes/nearby
     * Get nearby cafes using Haversine formula (requires auth)
     */
    public function nearby(Request $request): JsonResponse
    {
        // Accept both lat/lng and latitude/longitude
        $lat = $request->input('lat') ?? $request->input('latitude');
        $lng = $request->input('lng') ?? $request->input('longitude');

        $request->merge(['lat' => $lat, 'lng' => $lng]);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|numeric|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cafes = $this->cafeService->getNearbyCafes(
            (float) $lat,
            (float) $lng,
            $request->input('radius', 10)
        );

        return response()->json([
            'success' => true,
            'message' => 'Nearby cafes retrieved successfully',
            'data' => CafeResource::collection($cafes),
            'meta' => [
                'total' => $cafes->count(),
                'radius_km' => $request->input('radius', 10),
            ],
        ]);
    }

    /**
     * GET /api/v1/cafes/{id}
     * Get cafe detail with branches
     */
    public function show(int $id): JsonResponse
    {
        $cafe = $this->cafeService->getCafeById($id);

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'Cafe not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cafe details retrieved successfully',
            'data' => new CafeDetailResource($cafe),
        ]);
    }

    /**
     * GET /api/v1/cafes/{id}/branches
     * Get cafe's branches with hours and amenities
     */
    public function branches(int $id): JsonResponse
    {
        $branches = $this->cafeService->getCafeBranches($id);

        if ($branches === null) {
            return response()->json([
                'success' => false,
                'message' => 'Cafe not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Branches retrieved successfully',
            'data' => BranchResource::collection($branches),
            'meta' => [
                'total' => $branches->count(),
            ],
        ]);
    }
}
