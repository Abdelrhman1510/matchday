<?php

namespace App\Services;

use App\Models\User;
use App\Models\FanProfile;
use App\Notifications\EmailVerificationOtp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ProfileService
{
    protected ImageService $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * Get full user profile with relationships
     */
    public function getProfile(User $user): array
    {
        $user->load([
            'fanProfile.favoriteTeam',
            'loyaltyCard',
        ]);

        // Format avatar with multi-size URLs
        $avatar = null;
        if ($user->avatar && is_array($user->avatar)) {
            $avatar = [
                'original' => url('storage/' . $user->avatar['original']),
                'medium' => url('storage/' . $user->avatar['medium']),
                'thumbnail' => url('storage/' . $user->avatar['thumbnail']),
            ];
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $avatar,
            'role' => $user->role,
            'locale' => $user->locale ?? 'en',
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'is_active' => $user->is_active,
            'created_at' => $user->created_at->toISOString(),
            'fan_profile' => $user->fanProfile ? [
                'favorite_team_id' => $user->fanProfile->favorite_team_id,
                'favorite_team' => $user->fanProfile->favoriteTeam ? [
                    'id' => $user->fanProfile->favoriteTeam->id,
                    'name' => $user->fanProfile->favoriteTeam->name,
                    'logo' => $user->fanProfile->favoriteTeam->logo,
                ] : null,
                'matches_attended' => $user->fanProfile->matches_attended,
                'member_since' => $user->fanProfile->member_since?->toDateString(),
            ] : null,
            'loyalty_card' => $user->loyaltyCard ? [
                'card_number' => $user->loyaltyCard->card_number,
                'points' => $user->loyaltyCard->points,
                'tier' => $user->loyaltyCard->tier,
                'total_points_earned' => $user->loyaltyCard->total_points_earned,
                'issued_date' => $user->loyaltyCard->issued_date?->toDateString(),
            ] : null,
        ];
    }

    /**
     * Update user profile
     */
    public function updateProfile(User $user, array $data): User
    {
        $emailChanged = false;

        // Check if email is being changed
        if (isset($data['email']) && $data['email'] !== $user->email) {
            $emailChanged = true;
            $data['email_verified_at'] = null; // Reset verification
        }

        $user->update($data);

        // Send verification OTP if email changed
        if ($emailChanged) {
            $this->sendEmailVerificationOtp($user);
        }

        return $user->fresh();
    }

    /**
     * Upload and process avatar image
     */
    public function updateAvatar(User $user, $file): User
    {
        // Delete old avatar if exists
        if ($user->avatar && is_array($user->avatar)) {
            $this->imageService->delete($user->avatar);
        }

        // Store the raw file at avatars/ for simple access
        $file->store('avatars', 'public');

        // Upload image with multiple sizes
        $paths = $this->imageService->upload($file, 'avatars');

        // Update user avatar paths (stored as JSON)
        $user->update(['avatar' => $paths]);

        return $user->fresh();
    }

    /**
     * Update user password
     */
    public function updatePassword(User $user, string $currentPassword, string $newPassword): bool
    {
        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            return false;
        }

        // Update password
        $user->update(['password' => Hash::make($newPassword)]);

        // Revoke all tokens except current one (optional)
        // $user->tokens()->delete();

        return true;
    }

    /**
     * Update user locale preference
     */
    public function updateLocale(User $user, string $locale): User
    {
        $user->update(['locale' => $locale]);
        return $user->fresh();
    }

    /**
     * Update user device token for FCM
     */
    public function updateDeviceToken(User $user, string $deviceToken): User
    {
        $user->update(['device_token' => $deviceToken]);
        return $user->fresh();
    }

    /**
     * Update user's favorite team
     */
    public function updateFavoriteTeam(User $user, ?int $teamId): User
    {
        // Get or create fan profile
        $fanProfile = $user->fanProfile;

        if (!$fanProfile) {
            $fanProfile = FanProfile::create([
                'user_id' => $user->id,
                'favorite_team_id' => $teamId,
                'matches_attended' => 0,
                'member_since' => now(),
            ]);
        } else {
            $fanProfile->update(['favorite_team_id' => $teamId]);
        }

        return $user->fresh(['fanProfile.favoriteTeam']);
    }

    /**
     * Soft delete user account and revoke all tokens
     */
    public function deleteAccount(User $user): bool
    {
        // Revoke all tokens
        $user->tokens()->delete();

        // Soft delete user
        return $user->delete();
    }

    /**
     * Send email verification OTP
     */
    protected function sendEmailVerificationOtp(User $user): void
    {
        // Generate 6-digit OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store OTP in cache for 10 minutes
        Cache::put("email_verification_otp_{$user->id}", $otp, now()->addMinutes(10));

        // Send OTP via email (pass simple data instead of User object)
        $user->notify(new EmailVerificationOtp($otp, $user->name, $user->email));
    }
}
