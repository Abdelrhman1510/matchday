<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyCard;
use App\Http\Resources\LoyaltyCardResource;
use App\Http\Resources\LoyaltyTransactionResource;
use App\Services\LoyaltyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoyaltyController extends Controller
{
    protected LoyaltyService $loyaltyService;

    public function __construct(LoyaltyService $loyaltyService)
    {
        $this->loyaltyService = $loyaltyService;
    }

    /**
     * GET /api/v1/loyalty/card
     * Get user's loyalty card with points, tier, and progress
     */
    public function card(Request $request): JsonResponse
    {
        $user = $request->user();
        $loyaltyCard = $user->loyaltyCard;

        // Create loyalty card if not exists
        if (!$loyaltyCard) {
            $loyaltyCard = LoyaltyCard::create([
                'user_id' => $user->id,
                'card_number' => 'LC' . str_pad($user->id, 8, '0', STR_PAD_LEFT),
                'points' => 0,
                'tier' => 'bronze',
                'total_points_earned' => 0,
                'issued_date' => now(),
            ]);
        }

        $progress = $this->loyaltyService->getProgressToNextTier($loyaltyCard);

        return response()->json([
            'success' => true,
            'message' => 'Loyalty card retrieved successfully',
            'data' => [
                'id' => $loyaltyCard->id,
                'card_number' => $loyaltyCard->card_number,
                'points' => $loyaltyCard->points,
                'tier' => $loyaltyCard->tier,
                'total_points_earned' => $loyaltyCard->total_points_earned,
                'next_tier' => $progress['next_tier'],
                'progress' => $progress['progress_percentage'],
            ],
        ]);
    }

    /**
     * GET /api/v1/loyalty/transactions
     * Get user's loyalty transactions history (paginated)
     */
    public function transactions(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'sometimes|in:earned,redeemed',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $user = $request->user();
        $loyaltyCard = $user->loyaltyCard;

        if (!$loyaltyCard) {
            return response()->json([
                'success' => false,
                'message' => 'Loyalty card not found',
                'data' => [],
            ], 404);
        }

        $perPage = $request->input('per_page', 15);
        $type = $request->input('type');

        $query = $loyaltyCard->transactions()
            ->with(['booking.match.homeTeam', 'booking.match.awayTeam'])
            ->latest();

        if ($type) {
            $query->where('type', $type);
        }

        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Loyalty transactions retrieved successfully',
            'data' => LoyaltyTransactionResource::collection($transactions),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/v1/loyalty/tiers
     * Get all loyalty tiers with thresholds and benefits (public endpoint)
     */
    public function tiers(): JsonResponse
    {
        $tiers = $this->loyaltyService->getAllTiers();

        return response()->json([
            'success' => true,
            'message' => 'Loyalty tiers retrieved successfully',
            'data' => $tiers,
        ]);
    }

    /**
     * GET /api/v1/loyalty/progress
     * Get tier progress information
     */
    public function progress(Request $request): JsonResponse
    {
        $user = $request->user();
        $loyaltyCard = $user->loyaltyCard;

        if (!$loyaltyCard) {
            return response()->json([
                'success' => false,
                'message' => 'Loyalty card not found',
                'data' => [],
                'errors' => [],
            ], 404);
        }

        $progress = $this->loyaltyService->getProgressToNextTier($loyaltyCard);

        return response()->json([
            'success' => true,
            'message' => 'Tier progress retrieved successfully',
            'data' => [
                'current_tier' => $progress['current_tier'],
                'current_points' => $loyaltyCard->points,
                'next_tier' => $progress['next_tier'],
                'points_needed' => $progress['points_to_next'],
                'progress_percentage' => $progress['progress_percentage'],
            ],
        ]);
    }

    /**
     * POST /api/v1/loyalty/award
     * Award points to user's loyalty card
     */
    public function award(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
            'description' => 'required|string',
        ]);

        $user = $request->user();

        try {
            $transaction = $this->loyaltyService->awardPoints(
                $user,
                $validated['points'],
                $validated['description']
            );

            $loyaltyCard = $user->loyaltyCard->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Points awarded successfully',
                'data' => [
                    'transaction' => new LoyaltyTransactionResource($transaction),
                    'card' => new LoyaltyCardResource($loyaltyCard),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 400);
        }
    }

    /**
     * POST /api/v1/loyalty/redeem
     * Redeem points from user's loyalty card
     */
    public function redeem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $user = $request->user();

        try {
            $transaction = $this->loyaltyService->redeemPoints(
                $user,
                $validated['points'],
                'Points redeemed'
            );

            $loyaltyCard = $user->loyaltyCard->fresh();

            return response()->json([
                'success' => true,
                'message' => 'Points redeemed successfully',
                'data' => [
                    'transaction' => new LoyaltyTransactionResource($transaction),
                    'card' => new LoyaltyCardResource($loyaltyCard),
                    'remaining_points' => $loyaltyCard->points,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ], 422);
        }
    }
}
