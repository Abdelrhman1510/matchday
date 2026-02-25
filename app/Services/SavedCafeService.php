<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SavedCafeService
{
    /**
     * Get user's saved cafes
     */
    public function getSavedCafes(int $userId): Collection
    {
        $user = User::find($userId);
        
        if (!$user) {
            return new Collection();
        }

        return $user->savedCafes()
            ->withCount('branches')
            ->withPivot('created_at')
            ->orderBy('saved_cafes.created_at', 'desc')
            ->get();
    }

    /**
     * Save a cafe for user
     */
    public function saveCafe(int $userId, int $cafeId): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        $cafe = Cafe::find($cafeId);
        
        if (!$cafe) {
            return [
                'success' => false,
                'message' => 'Cafe not found',
            ];
        }

        // Check if already saved
        if ($user->savedCafes()->where('cafe_id', $cafeId)->exists()) {
            return [
                'success' => false,
                'message' => 'Cafe already saved',
            ];
        }

        $user->savedCafes()->attach($cafeId);

        return [
            'success' => true,
            'message' => 'Cafe saved successfully',
        ];
    }

    /**
     * Unsave a cafe for user
     */
    public function unsaveCafe(int $userId, int $cafeId): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
            ];
        }

        // Check if cafe is saved
        if (!$user->savedCafes()->where('cafe_id', $cafeId)->exists()) {
            return [
                'success' => false,
                'message' => 'Cafe not in saved list',
            ];
        }

        $user->savedCafes()->detach($cafeId);

        return [
            'success' => true,
            'message' => 'Cafe removed from saved list',
        ];
    }
}
