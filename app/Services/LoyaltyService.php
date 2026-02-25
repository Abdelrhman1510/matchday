<?php

namespace App\Services;

use App\Models\User;
use App\Models\LoyaltyCard;
use App\Models\LoyaltyTransaction;
use App\Models\Achievement;
use App\Events\PointsEarned;
use App\Events\TierUpgraded;
use App\Events\AchievementUnlocked;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    // Tier thresholds
    const TIERS = [
        'bronze' => 0,
        'silver' => 100,
        'gold' => 500,
        'platinum' => 1000,
    ];

    /**
     * Award points to user
     */
    public function awardPoints(User $user, int $points, string $description, ?int $bookingId = null): LoyaltyTransaction
    {
        return DB::transaction(function () use ($user, $points, $description, $bookingId) {
            $loyaltyCard = $user->loyaltyCard;

            if (!$loyaltyCard) {
                throw new \Exception('User does not have a loyalty card');
            }

            // Create transaction
            $transaction = LoyaltyTransaction::create([
                'loyalty_card_id' => $loyaltyCard->id,
                'booking_id' => $bookingId,
                'points' => $points,
                'type' => 'earned',
                'description' => $description,
            ]);

            // Update loyalty card
            $loyaltyCard->increment('points', $points);
            $loyaltyCard->increment('total_points_earned', $points);

            // Fire event
            event(new PointsEarned($user, $points, $description, $transaction));

            // Check for tier upgrade
            $this->checkTierUpgrade($user);

            // Check for achievements
            $this->checkAchievements($user);

            return $transaction;
        });
    }

    /**
     * Redeem points from user
     */
    public function redeemPoints(User $user, int $points, string $description): LoyaltyTransaction
    {
        return DB::transaction(function () use ($user, $points, $description) {
            $loyaltyCard = $user->loyaltyCard;

            if (!$loyaltyCard) {
                throw new \Exception('User does not have a loyalty card');
            }

            if ($loyaltyCard->points < $points) {
                throw new \Exception('Insufficient points');
            }

            // Create transaction
            $transaction = LoyaltyTransaction::create([
                'loyalty_card_id' => $loyaltyCard->id,
                'booking_id' => null,
                'points' => $points,
                'type' => 'redeemed',
                'description' => $description,
            ]);

            // Deduct points
            $loyaltyCard->decrement('points', $points);

            return $transaction;
        });
    }

    /**
     * Check and upgrade tier based on total_points_earned
     */
    public function checkTierUpgrade(User $user): ?string
    {
        $loyaltyCard = $user->loyaltyCard;

        if (!$loyaltyCard) {
            return null;
        }

        $totalPoints = $loyaltyCard->points;
        $currentTier = $loyaltyCard->tier;
        $newTier = $this->calculateTier($totalPoints);

        // Check if tier changed
        if ($newTier !== $currentTier) {
            $loyaltyCard->update(['tier' => $newTier]);

            // Fire event
            event(new TierUpgraded($user, $currentTier, $newTier));

            return $newTier;
        }

        return null;
    }

    /**
     * Calculate tier based on total points
     */
    public function calculateTier(int $totalPoints): string
    {
        if ($totalPoints >= self::TIERS['platinum']) {
            return 'platinum';
        } elseif ($totalPoints >= self::TIERS['gold']) {
            return 'gold';
        } elseif ($totalPoints >= self::TIERS['silver']) {
            return 'silver';
        }
        return 'bronze';
    }

    /**
     * Get progress to next tier
     */
    public function getProgressToNextTier(LoyaltyCard $loyaltyCard): array
    {
        $currentTier = $loyaltyCard->tier;
        $totalPoints = $loyaltyCard->points;

        $tierOrder = ['bronze', 'silver', 'gold', 'platinum'];
        $currentIndex = array_search($currentTier, $tierOrder);

        // Already at max tier
        if ($currentIndex === count($tierOrder) - 1) {
            return [
                'current_tier' => $currentTier,
                'next_tier' => null,
                'points_to_next' => 0,
                'progress_percentage' => 100,
            ];
        }

        $nextTier = $tierOrder[$currentIndex + 1];
        $currentThreshold = self::TIERS[$currentTier];
        $nextThreshold = self::TIERS[$nextTier];

        $pointsNeeded = $nextThreshold - $totalPoints;
        $pointsInRange = $nextThreshold - $currentThreshold;
        $pointsProgress = $totalPoints - $currentThreshold;
        $progressPercentage = round(($pointsProgress / $pointsInRange) * 100, 2);

        return [
            'current_tier' => $currentTier,
            'next_tier' => $nextTier,
            'points_to_next' => max(0, $pointsNeeded),
            'progress_percentage' => min(100, $progressPercentage),
        ];
    }

    /**
     * Check and unlock achievements based on criteria
     */
    public function checkAchievements(User $user): array
    {
        $unlockedAchievements = [];

        // Get all achievements
        $achievements = Achievement::all();

        // Get already unlocked achievement IDs
        $unlockedIds = $user->achievements()->pluck('achievements.id')->toArray();

        foreach ($achievements as $achievement) {
            // Skip if already unlocked
            if (in_array($achievement->id, $unlockedIds)) {
                continue;
            }

            // Check if criteria met
            if ($this->checkAchievementCriteria($user, $achievement)) {
                // Unlock achievement
                $user->achievements()->attach($achievement->id, [
                    'unlocked_at' => now(),
                ]);

                // Award points
                if ($achievement->points_reward > 0) {
                    $this->awardPoints(
                        $user,
                        $achievement->points_reward,
                        "Achievement unlocked: {$achievement->name}"
                    );
                }

                // Fire event
                event(new AchievementUnlocked($user, $achievement));

                $unlockedAchievements[] = $achievement;
            }
        }

        return $unlockedAchievements;
    }

    /**
     * Check if achievement criteria is met
     */
    private function checkAchievementCriteria(User $user, Achievement $achievement): bool
    {
        $criteriaType = $achievement->criteria_type;
        $criteriaValue = $achievement->criteria_value;

        return match ($criteriaType) {
            'total_bookings' => $user->bookings()->count() >= $criteriaValue,
            'total_points' => $user->loyaltyCard?->total_points_earned >= $criteriaValue,
            'matches_attended' => $user->fanProfile?->matches_attended >= $criteriaValue,
            'consecutive_bookings' => $this->checkConsecutiveBookings($user, $criteriaValue),
            default => false,
        };
    }

    /**
     * Check consecutive bookings (bookings in consecutive weeks/months)
     */
    private function checkConsecutiveBookings(User $user, int $required): bool
    {
        // Simple implementation: check if user has bookings in last N weeks
        $bookingsCount = $user->bookings()
            ->where('created_at', '>=', now()->subWeeks($required))
            ->count();

        return $bookingsCount >= $required;
    }

    /**
     * Get all tiers with thresholds
     */
    public function getAllTiers(): array
    {
        return [
            [
                'name' => 'bronze',
                'threshold' => self::TIERS['bronze'],
                'benefits' => [
                    'Earn 1 point per SAR spent',
                    'Access to standard seating',
                ],
            ],
            [
                'name' => 'silver',
                'threshold' => self::TIERS['silver'],
                'benefits' => [
                    'Earn 1.5 points per SAR spent',
                    'Early booking access (24h)',
                    '5% discount on bookings',
                ],
            ],
            [
                'name' => 'gold',
                'threshold' => self::TIERS['gold'],
                'benefits' => [
                    'Earn 2 points per SAR spent',
                    'Early booking access (48h)',
                    '10% discount on bookings',
                    'Priority customer support',
                ],
            ],
            [
                'name' => 'platinum',
                'threshold' => self::TIERS['platinum'],
                'benefits' => [
                    'Earn 3 points per SAR spent',
                    'Early booking access (72h)',
                    '15% discount on bookings',
                    'VIP lounge access',
                    'Dedicated account manager',
                ],
            ],
        ];
    }
}
