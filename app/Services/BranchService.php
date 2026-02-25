<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Review;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BranchService
{
    /**
     * Get branch by ID with full details
     */
    public function getBranchById(int $id): ?Branch
    {
        return Branch::query()
            ->with([
                'cafe',
                'hours',
                'amenities',
                'seatingSections',
                'matches' => function ($query) {
                    $query->where('status', 'live')
                        ->orWhere(function ($q) {
                            $q->where('status', 'upcoming')
                              ->where('kick_off', '>', now());
                        })
                        ->with(['homeTeam', 'awayTeam'])
                        ->orderBy('kick_off')
                        ->limit(5);
                }
            ])
            ->find($id);
    }

    /**
     * Get branch matches (live and upcoming)
     */
    public function getBranchMatches(int $branchId): ?\Illuminate\Database\Eloquent\Collection
    {
        $branch = Branch::find($branchId);
        
        if (!$branch) {
            return null;
        }

        return $branch->matches()
            ->with(['homeTeam', 'awayTeam', 'branch.cafe'])
            ->where(function ($query) {
                $query->where('status', 'live')
                    ->orWhere(function ($q) {
                        $q->where('status', 'upcoming');
                    });
            })
            ->where('is_published', true)
            ->orderByRaw("CASE WHEN status = 'live' THEN 0 ELSE 1 END")
            ->orderBy('kick_off')
            ->get();
    }

    /**
     * Get branch reviews with pagination
     */
    public function getBranchReviews(int $branchId, int $perPage = 15): ?LengthAwarePaginator
    {
        $branch = Branch::find($branchId);
        
        if (!$branch) {
            return null;
        }

        return $branch->reviews()
            ->with(['user' => function ($query) {
                $query->select('id', 'name', 'profile_picture');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a review for a branch
     */
    public function createReview(int $userId, int $branchId, array $data): array
    {
        $branch = Branch::find($branchId);
        
        if (!$branch) {
            return [
                'success' => false,
                'message' => 'Branch not found',
            ];
        }

        // Check if user already reviewed this branch
        $existingReview = Review::where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->first();

        if ($existingReview) {
            return [
                'success' => false,
                'message' => 'You have already reviewed this branch',
            ];
        }

        // Verify booking if booking_id is provided
        if (isset($data['booking_id'])) {
            $booking = \App\Models\Booking::where('id', $data['booking_id'])
                ->where('user_id', $userId)
                ->whereHas('match', function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->first();

            if (!$booking) {
                return [
                    'success' => false,
                    'message' => 'Invalid booking or booking does not belong to this branch',
                ];
            }
        }

        DB::beginTransaction();
        try {
            // Create review
            $review = Review::create([
                'user_id' => $userId,
                'branch_id' => $branchId,
                'booking_id' => $data['booking_id'] ?? null,
                'rating' => $data['rating'],
                'comment' => $data['comment'] ?? null,
            ]);

            // Update branch cafe's average rating and review count
            $this->updateCafeRating($branch->cafe_id);

            DB::commit();

            return [
                'success' => true,
                'review' => $review->load('user'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to create review: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Update cafe's average rating based on all branch reviews
     */
    private function updateCafeRating(int $cafeId): void
    {
        $cafe = \App\Models\Cafe::find($cafeId);
        
        if (!$cafe) {
            return;
        }

        // Get all reviews from all branches of this cafe
        $reviews = Review::whereHas('branch', function ($query) use ($cafeId) {
            $query->where('cafe_id', $cafeId);
        })->get();

        $cafe->update([
            'avg_rating' => $reviews->avg('rating') ?? 0,
            'total_reviews' => $reviews->count(),
        ]);
    }
}
