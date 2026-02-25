<?php

namespace App\Http\Controllers;

use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Http\Resources\AchievementResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    /**
     * GET /api/v1/achievements
     * Get all achievements with locked/unlocked status for authenticated user
     */
    public function index(Request $request): JsonResponse
    {
        $query = Achievement::query();

        if ($request->has('category')) {
            $query->where('category', $request->input('category'));
        }

        $achievements = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Achievements retrieved successfully',
            'data' => AchievementResource::collection($achievements),
            'meta' => [
                'total' => $achievements->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/achievements/unlocked
     * Get only unlocked achievements for authenticated user
     */
    public function unlocked(Request $request): JsonResponse
    {
        $user = $request->user();

        $achievements = $user->achievements()
            ->withPivot('unlocked_at')
            ->latest('user_achievements.unlocked_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Unlocked achievements retrieved successfully',
            'data' => $achievements->map(function ($achievement) {
                $unlockedAt = \Carbon\Carbon::parse($achievement->pivot->unlocked_at);
                return [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'icon' => $achievement->icon ? url('storage/' . $achievement->icon) : null,
                    'points_reward' => $achievement->points_reward,
                    'requirement' => $achievement->requirement,
                    'unlocked_at' => $unlockedAt->format('Y-m-d H:i:s'),
                    'unlocked_at_human' => $unlockedAt->diffForHumans(),
                ];
            }),
            'meta' => [
                'total_unlocked' => $achievements->count(),
            ],
        ]);
    }

    /**
     * GET /api/v1/achievements/my
     * Get only unlocked achievements for authenticated user (alias)
     */
    public function my(Request $request): JsonResponse
    {
        return $this->unlocked($request);
    }

    /**
     * POST /api/v1/achievements/{id}/unlock
     * Unlock an achievement for the authenticated user
     */
    public function unlock(Request $request, int $id): JsonResponse
    {
        $achievement = Achievement::find($id);

        if (!$achievement) {
            return response()->json([
                'success' => false,
                'message' => 'Achievement not found',
            ], 404);
        }

        $user = $request->user();

        // Check if already unlocked
        $existing = UserAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Achievement already unlocked',
                'errors' => ['achievement' => ['This achievement has already been unlocked.']],
            ], 422);
        }

        $now = now();
        UserAchievement::create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => $now,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Achievement unlocked successfully',
            'data' => [
                'achievement' => [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'description' => $achievement->description,
                    'requirement' => $achievement->requirement,
                ],
                'unlocked_at' => $now->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * GET /api/v1/achievements/progress
     * Get achievement progress for authenticated user
     */
    public function progress(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalAchievements = Achievement::count();
        $unlockedCount = UserAchievement::where('user_id', $user->id)->count();
        $progressPercentage = $totalAchievements > 0
            ? round(($unlockedCount / $totalAchievements) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'message' => 'Achievement progress retrieved successfully',
            'data' => [
                'total_achievements' => $totalAchievements,
                'unlocked_count' => $unlockedCount,
                'progress_percentage' => $progressPercentage,
            ],
        ]);
    }
}
