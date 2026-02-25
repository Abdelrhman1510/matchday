<?php

namespace App\Http\Controllers;

use App\Http\Resources\BranchDetailResource;
use App\Http\Resources\MatchResource;
use App\Http\Resources\ReviewResource;
use App\Services\BranchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BranchController extends Controller
{
    protected BranchService $branchService;

    public function __construct(BranchService $branchService)
    {
        $this->branchService = $branchService;
    }

    /**
     * GET /api/v1/branches/{id}
     * Get branch detail with hours, amenities, sections, and current matches
     */
    public function show(int $id): JsonResponse
    {
        $branch = $this->branchService->getBranchById($id);

        if (!$branch) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found',
                'errors' => ['branch' => ['Branch not found']],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Branch details retrieved successfully',
            'data' => new BranchDetailResource($branch),
        ]);
    }

    /**
     * GET /api/v1/branches/{id}/matches
     * Get live and upcoming matches at branch
     */
    public function matches(int $id): JsonResponse
    {
        $matches = $this->branchService->getBranchMatches($id);

        if ($matches === null) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found',
                'errors' => ['branch' => ['Branch not found']],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Branch matches retrieved successfully',
            'data' => MatchResource::collection($matches),
            'meta' => [
                'total' => $matches->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/branches/{id}/reviews
     * Get paginated reviews for branch
     */
    public function reviews(int $id): JsonResponse
    {
        $reviews = $this->branchService->getBranchReviews($id);

        if ($reviews === null) {
            return response()->json([
                'success' => false,
                'message' => 'Branch not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reviews retrieved successfully',
            'data' => ReviewResource::collection($reviews->items()),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/branches/{id}/reviews
     * Create a review for branch (requires auth)
     */
    public function createReview(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'booking_id' => 'nullable|integer|exists:bookings,id',
        ]);

        $result = $this->branchService->createReview(
            $request->user()->id,
            $id,
            $request->only(['rating', 'comment', 'booking_id'])
        );

        if (!$result['success']) {
            $statusCode = $result['message'] === 'Branch not found' ? 404 : 422;
            $response = [
                'success' => false,
                'message' => $result['message'],
            ];
            if ($statusCode === 422) {
                $response['errors'] = ['review' => [$result['message']]];
            }
            return response()->json($response, $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => 'Review created successfully',
            'data' => new ReviewResource($result['review']),
        ], 201);
    }
}
