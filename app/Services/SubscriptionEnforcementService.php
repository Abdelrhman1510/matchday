<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\Booking;
use App\Models\CafeSubscription;
use App\Models\GameMatch;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;

class SubscriptionEnforcementService
{
    /**
     * Returns the active subscription plan for a cafe, including grace period plans.
     */
    private function getActivePlan(Cafe $cafe): ?SubscriptionPlan
    {
        // First check for an active subscription
        $subscription = $cafe->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->with('plan')
            ->latest('expires_at')
            ->first();

        if ($subscription) {
            return $subscription->plan;
        }

        // Check grace period (expired ≤7 days ago)
        if ($this->isInGracePeriod($cafe)) {
            $subscription = $cafe->subscriptions()
                ->where('status', 'active')
                ->where('expires_at', '<=', now())
                ->where('expires_at', '>=', now()->subDays(7))
                ->with('plan')
                ->latest('expires_at')
                ->first();

            // Also check for 'expired' status in grace window
            if (!$subscription) {
                $subscription = $cafe->subscriptions()
                    ->where('status', 'expired')
                    ->where('expires_at', '<=', now())
                    ->where('expires_at', '>=', now()->subDays(7))
                    ->with('plan')
                    ->latest('expires_at')
                    ->first();
            }

            return $subscription?->plan;
        }

        return null;
    }

    /**
     * Check whether a cafe can create a new branch.
     * Returns ['allowed' => bool, 'reason' => string, 'limit' => int|null, 'current' => int]
     */
    public function canCreateBranch(Cafe $cafe): array
    {
        $plan = $this->getActivePlan($cafe);

        if (!$plan) {
            return $this->blocked('No active subscription. Please subscribe to create branches.', 0, $cafe->branches()->count());
        }

        if ($plan->max_branches === null) {
            return $this->allowed();
        }

        $current = $cafe->branches()->count();

        if ($current >= $plan->max_branches) {
            return $this->blocked(
                "Branch limit reached. Your {$plan->name} plan allows {$plan->max_branches} branch(es).",
                $plan->max_branches,
                $current
            );
        }

        return $this->allowed($plan->max_branches, $current);
    }

    /**
     * Check whether a cafe can create a new match this month.
     */
    public function canCreateMatch(Cafe $cafe): array
    {
        $plan = $this->getActivePlan($cafe);

        if (!$plan) {
            return $this->blocked('No active subscription. Please subscribe to create matches.', 0, 0);
        }

        if ($plan->max_matches_per_month === null) {
            return $this->allowed();
        }

        // Count matches created this calendar month across all branches
        $branchIds = $cafe->branches()->pluck('id');
        $current = GameMatch::whereIn('branch_id', $branchIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if ($current >= $plan->max_matches_per_month) {
            return $this->blocked(
                "Monthly match limit reached. Your {$plan->name} plan allows {$plan->max_matches_per_month} matches per month.",
                $plan->max_matches_per_month,
                $current
            );
        }

        return $this->allowed($plan->max_matches_per_month, $current);
    }

    /**
     * Check whether a cafe can receive a new booking this month.
     */
    public function canReceiveBooking(Cafe $cafe): array
    {
        $plan = $this->getActivePlan($cafe);

        if (!$plan) {
            return $this->blocked('Cafe has no active subscription and cannot receive bookings.', 0, 0);
        }

        if ($plan->max_bookings_per_month === null) {
            return $this->allowed();
        }

        // Count bookings created this calendar month across all branches
        $branchIds = $cafe->branches()->pluck('id');
        $current = Booking::whereIn('branch_id', $branchIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        if ($current >= $plan->max_bookings_per_month) {
            return $this->blocked(
                "Monthly booking limit reached. The {$plan->name} plan allows {$plan->max_bookings_per_month} bookings per month.",
                $plan->max_bookings_per_month,
                $current
            );
        }

        return $this->allowed($plan->max_bookings_per_month, $current);
    }

    /**
     * Check whether a cafe can add another staff member.
     */
    public function canAddStaff(Cafe $cafe): array
    {
        $plan = $this->getActivePlan($cafe);

        if (!$plan) {
            return $this->blocked('No active subscription. Please subscribe to add staff.', 0, $cafe->staffMembers()->count());
        }

        if ($plan->max_staff_members === null) {
            return $this->allowed();
        }

        $current = $cafe->staffMembers()->count();

        if ($current >= $plan->max_staff_members) {
            return $this->blocked(
                "Staff limit reached. Your {$plan->name} plan allows {$plan->max_staff_members} staff member(s).",
                $plan->max_staff_members,
                $current
            );
        }

        return $this->allowed($plan->max_staff_members, $current);
    }

    /**
     * Check whether a cafe can create another offer.
     */
    public function canCreateOffer(Cafe $cafe): array
    {
        $plan = $this->getActivePlan($cafe);

        if (!$plan) {
            return $this->blocked('No active subscription. Please subscribe to create offers.', 0, $cafe->offers()->count());
        }

        if ($plan->max_offers === null) {
            return $this->allowed();
        }

        $current = $cafe->offers()->count();

        if ($current >= $plan->max_offers) {
            return $this->blocked(
                "Offer limit reached. Your {$plan->name} plan allows {$plan->max_offers} offer(s).",
                $plan->max_offers,
                $current
            );
        }

        return $this->allowed($plan->max_offers, $current);
    }

    /**
     * Check whether a cafe has access to a specific feature.
     */
    public function hasFeature(Cafe $cafe, string $feature): bool
    {
        $plan = $this->getActivePlan($cafe);

        if (!$plan) {
            return false;
        }

        return (bool) ($plan->{$feature} ?? false);
    }

    /**
     * Get full usage summary for the subscription/usage endpoint.
     */
    public function getUsageSummary(Cafe $cafe): array
    {
        $plan = $this->getActivePlan($cafe);

        $branchIds = $cafe->branches()->pluck('id');

        $branchCount = $cafe->branches()->count();
        $staffCount = $cafe->staffMembers()->count();
        $offerCount = $cafe->offers()->count();

        $matchesThisMonth = GameMatch::whereIn('branch_id', $branchIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $bookingsThisMonth = Booking::whereIn('branch_id', $branchIds)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
            ] : null,
            'grace_period' => $this->isInGracePeriod($cafe),
            'grace_period_days_left' => $this->getGracePeriodDaysLeft($cafe),
            'usage' => [
                'branches' => [
                    'current' => $branchCount,
                    'limit' => $plan?->max_branches,
                    'unlimited' => $plan?->max_branches === null,
                ],
                'matches_this_month' => [
                    'current' => $matchesThisMonth,
                    'limit' => $plan?->max_matches_per_month,
                    'unlimited' => $plan?->max_matches_per_month === null,
                ],
                'bookings_this_month' => [
                    'current' => $bookingsThisMonth,
                    'limit' => $plan?->max_bookings_per_month,
                    'unlimited' => $plan?->max_bookings_per_month === null,
                ],
                'staff_members' => [
                    'current' => $staffCount,
                    'limit' => $plan?->max_staff_members,
                    'unlimited' => $plan?->max_staff_members === null,
                ],
                'offers' => [
                    'current' => $offerCount,
                    'limit' => $plan?->max_offers,
                    'unlimited' => $plan?->max_offers === null,
                ],
            ],
            'features' => [
                'has_analytics' => $plan?->has_analytics ?? false,
                'has_branding' => $plan?->has_branding ?? false,
                'has_priority_support' => $plan?->has_priority_support ?? false,
                'has_chat' => $plan?->has_chat ?? false,
                'has_qr_scanner' => $plan?->has_qr_scanner ?? false,
                'has_occupancy_tracking' => $plan?->has_occupancy_tracking ?? false,
            ],
        ];
    }

    /**
     * Check whether a cafe's subscription is within the 7-day grace period.
     */
    public function isInGracePeriod(Cafe $cafe): bool
    {
        // Check for subscription expired within last 7 days
        return $cafe->subscriptions()
            ->whereIn('status', ['active', 'expired'])
            ->where('expires_at', '<=', now())
            ->where('expires_at', '>=', now()->subDays(7))
            ->exists();
    }

    /**
     * Get the number of grace period days left.
     */
    public function getGracePeriodDaysLeft(Cafe $cafe): int
    {
        $subscription = $cafe->subscriptions()
            ->whereIn('status', ['active', 'expired'])
            ->where('expires_at', '<=', now())
            ->where('expires_at', '>=', now()->subDays(7))
            ->latest('expires_at')
            ->first();

        if (!$subscription) {
            return 0;
        }

        $graceEnd = $subscription->expires_at->copy()->addDays(7);
        $daysLeft = now()->diffInDays($graceEnd, false);

        return max(0, (int) ceil($daysLeft));
    }

    /**
     * Check if the cafe has any active subscription (including grace period).
     */
    public function hasActiveSubscription(Cafe $cafe): bool
    {
        return $this->getActivePlan($cafe) !== null;
    }

    // ─── Helper Methods ──────────────────────────────────────────────────────

    private function allowed(?int $limit = null, ?int $current = null): array
    {
        return [
            'allowed' => true,
            'reason' => '',
            'limit' => $limit,
            'current' => $current ?? 0,
        ];
    }

    private function blocked(string $reason, ?int $limit, int $current): array
    {
        return [
            'allowed' => false,
            'reason' => $reason,
            'limit' => $limit,
            'current' => $current,
        ];
    }
}
