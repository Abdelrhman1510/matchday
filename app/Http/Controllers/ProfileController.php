<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateAvatarRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateLocaleRequest;
use App\Http\Requests\UpdateDeviceTokenRequest;
use App\Http\Requests\UpdateFavoriteTeamRequest;
use App\Services\ProfileService;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    protected ProfileService $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * Get user profile with relationships
     * GET /api/v1/profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $this->profileService->getProfile($user);

        return response()->json([
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => $profile,
        ]);
    }

    /**
     * Update user profile
     * PUT /api/v1/profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        // Manual validation with user ID for unique checks
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => [
                'sometimes',
                'string',
                'max:20',
                \Illuminate\Validation\Rule::unique('users')->ignore($user->id),
            ],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                \Illuminate\Validation\Rule::unique('users')->ignore($user->id),
            ],
            'locale' => ['sometimes', 'string', \Illuminate\Validation\Rule::in(['en', 'ar'])],
        ]);

        $updatedUser = $this->profileService->updateProfile($user, $validated);
        $profile = $this->profileService->getProfile($updatedUser);

        $message = 'Profile updated successfully';
        if (isset($validated['email']) && $validated['email'] !== $user->email) {
            $message = 'Profile updated successfully. Please verify your new email address.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $profile,
        ]);
    }

    /**
     * Upload avatar image
     * POST /api/v1/profile/avatar
     */
    public function updateAvatar(UpdateAvatarRequest $request): JsonResponse
    {
        $user = $request->user();

        $updatedUser = $this->profileService->updateAvatar($user, $request->file('avatar'));
        $profile = $this->profileService->getProfile($updatedUser);

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated successfully',
            'data' => [
                'avatar_url' => $profile['avatar']['original'] ?? null,
                'avatar' => $profile['avatar'],
            ],
        ]);
    }

    /**
     * Update password
     * PUT /api/v1/profile/password
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $success = $this->profileService->updatePassword(
            $user,
            $request->input('current_password'),
            $request->input('password')
        );

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
                'errors' => ['current_password' => ['The provided current password is incorrect.']],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully',
        ]);
    }

    /**
     * Update locale preference
     * PUT /api/v1/profile/locale
     */
    public function updateLocale(UpdateLocaleRequest $request): JsonResponse
    {
        $user = $request->user();

        $updatedUser = $this->profileService->updateLocale($user, $request->input('locale'));
        $profile = $this->profileService->getProfile($updatedUser);

        return response()->json([
            'success' => true,
            'message' => 'Locale preference updated successfully',
            'data' => $profile,
        ]);
    }

    /**
     * Update device token for FCM
     * PUT /api/v1/profile/device-token
     */
    public function updateDeviceToken(UpdateDeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->profileService->updateDeviceToken($user, $request->input('device_token'));

        return response()->json([
            'success' => true,
            'message' => 'Device token updated successfully',
        ]);
    }

    /**
     * Update favorite team
     * PUT /api/v1/profile/favorite-team
     */
    public function updateFavoriteTeam(UpdateFavoriteTeamRequest $request): JsonResponse
    {
        $user = $request->user();

        $updatedUser = $this->profileService->updateFavoriteTeam($user, $request->input('team_id'));
        $profile = $this->profileService->getProfile($updatedUser);

        return response()->json([
            'success' => true,
            'message' => 'Favorite team updated successfully',
            'data' => $profile,
        ]);
    }

    /**
     * Delete user account (soft delete)
     * DELETE /api/v1/profile
     */
    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        // Verify password before deletion
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password',
                'errors' => ['password' => ['The provided password is incorrect.']],
            ], 422);
        }

        $this->profileService->deleteAccount($user);

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    /**
     * Get user activity feed (bookings, achievements, tier upgrades)
     * GET /api/v1/profile/activity
     */
    public function activity(Request $request): JsonResponse
    {
        $user = $request->user();
        $activities = [];

        // Get recent bookings with points earned
        $recentBookings = $user->bookings()
            ->with(['match.homeTeam', 'match.awayTeam', 'branch.cafe'])
            ->latest()
            ->take(10)
            ->get();

        foreach ($recentBookings as $booking) {
            // Find associated loyalty transaction
            $transaction = \App\Models\LoyaltyTransaction::where('booking_id', $booking->id)
                ->where('type', 'earned')
                ->first();

            $activities[] = [
                'type' => 'booking',
                'id' => $booking->id,
                'title' => 'Match Booking',
                'description' => "{$booking->match->homeTeam->name} vs {$booking->match->awayTeam->name}",
                'points' => $transaction ? $transaction->points : 0,
                'details' => [
                    'booking_code' => $booking->booking_code,
                    'status' => $booking->status,
                    'match_date' => $booking->match->match_date?->format('Y-m-d'),
                    'cafe' => $booking->branch->cafe->name,
                ],
                'date' => $booking->created_at->toISOString(),
                'date_human' => $booking->created_at->diffForHumans(),
            ];
        }

        // Get recent achievements
        $recentAchievements = $user->achievements()
            ->withPivot('unlocked_at')
            ->latest('user_achievements.unlocked_at')
            ->take(10)
            ->get();

        foreach ($recentAchievements as $achievement) {
            $unlockedAt = \Carbon\Carbon::parse($achievement->pivot->unlocked_at);
            $activities[] = [
                'type' => 'achievement',
                'id' => $achievement->id,
                'title' => 'Achievement Unlocked',
                'description' => $achievement->name,
                'points' => $achievement->points_reward,
                'details' => [
                    'achievement_description' => $achievement->description,
                    'icon' => $achievement->icon ? url('storage/' . $achievement->icon) : null,
                ],
                'date' => $unlockedAt->toISOString(),
                'date_human' => $unlockedAt->diffForHumans(),
            ];
        }

        // Sort all activities by date (descending) and take last 20
        usort($activities, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        $activities = array_slice($activities, 0, 20);

        return response()->json([
            'success' => true,
            'message' => 'Activity feed retrieved successfully',
            'data' => $activities,
            'meta' => [
                'total' => count($activities),
            ],
        ]);
    }
}
