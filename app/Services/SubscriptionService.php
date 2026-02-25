<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\CafeSubscription;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    /**
     * Get current subscription with calculated fields
     */
    public function getCurrentSubscription(Cafe $cafe): ?array
    {
        $subscription = $cafe->subscriptions()
            ->with(['plan', 'paymentMethod'])
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->orderBy('expires_at', 'desc')
            ->first();

        if (!$subscription) {
            return null;
        }

        return $this->formatSubscriptionData($subscription);
    }

    /**
     * Get all available subscription plans
     */
    public function getAllPlans()
    {
        return Cache::remember('subscription_plans_active', 3600, function () {
            return SubscriptionPlan::active()
                ->orderBy('price', 'asc')
                ->get();
        });
    }

    /**
     * Upgrade or change subscription plan
     */
    public function upgradePlan(Cafe $cafe, int $planId, int $paymentMethodId): array
    {
        return DB::transaction(function () use ($cafe, $planId, $paymentMethodId) {
            $plan = SubscriptionPlan::findOrFail($planId);
            $paymentMethod = null;
            
            if ($paymentMethodId) {
                $paymentMethod = PaymentMethod::where('id', $paymentMethodId)
                    ->where('user_id', $cafe->owner_id)
                    ->first();

                if (!$paymentMethod) {
                    throw new \Exception('Payment method not found or does not belong to this user');
                }
            }

            // Find existing active subscription
            $existingSubscription = $cafe->subscriptions()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();

            if ($existingSubscription) {
                // Update existing subscription with new plan
                $existingSubscription->update([
                    'plan_id' => $plan->id,
                    'starts_at' => now(),
                    'expires_at' => now()->addMonth(),
                    'payment_method_id' => $paymentMethodId ?: $existingSubscription->payment_method_id,
                    'auto_renew' => true,
                ]);

                $subscription = $existingSubscription;
            } else {
                // Create new subscription
                $subscription = CafeSubscription::create([
                    'cafe_id' => $cafe->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => now()->addMonth(),
                    'payment_method_id' => $paymentMethodId ?: null,
                    'auto_renew' => true,
                ]);
            }

            // Create payment record
            $payment = Payment::create([
                'user_id' => $cafe->owner_id,
                'payment_method_id' => $paymentMethodId ?: null,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'status' => 'paid',
                'type' => 'subscription',
                'description' => "Subscription payment for {$plan->name} plan",
                'gateway_ref' => 'SUB_' . strtoupper(uniqid()),
                'paid_at' => now(),
            ]);

            // Update cafe subscription plan
            $cafe->update([
                'subscription_plan' => $plan->slug,
            ]);

            // Clear cache
            Cache::forget("cafe_subscription_{$cafe->id}");

            return [
                'subscription' => $this->formatSubscriptionData($subscription->load(['plan', 'paymentMethod'])),
                'payment' => $payment,
            ];
        });
    }

    /**
     * Cancel subscription at period end
     */
    public function cancelSubscription(Cafe $cafe): bool
    {
        $subscription = $cafe->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if (!$subscription) {
            return false;
        }

        // Cancel subscription - set status and stop auto-renew
        $subscription->update([
            'status' => 'cancelled',
            'auto_renew' => false,
        ]);

        Cache::forget("cafe_subscription_{$cafe->id}");

        return true;
    }

    /**
     * Toggle auto-renew setting
     */
    public function toggleAutoRenew(Cafe $cafe, bool $autoRenew): bool
    {
        $subscription = $cafe->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if (!$subscription) {
            return false;
        }

        $subscription->update([
            'auto_renew' => $autoRenew,
        ]);

        Cache::forget("cafe_subscription_{$cafe->id}");

        return true;
    }

    /**
     * Process subscription renewal
     */
    public function processRenewal(CafeSubscription $subscription): bool
    {
        if (!$subscription->auto_renew) {
            return false;
        }

        if (!$subscription->paymentMethod) {
            return false;
        }

        return DB::transaction(function () use ($subscription) {
            try {
                $plan = $subscription->plan;
                $cafe = $subscription->cafe;

                // Create payment record
                $payment = Payment::create([
                    'user_id' => $cafe->owner_id,
                    'payment_method_id' => $subscription->payment_method_id,
                    'amount' => $plan->price,
                    'currency' => $plan->currency,
                    'status' => 'paid',
                    'type' => 'subscription',
                    'description' => "Automatic renewal for {$plan->name} plan",
                    'gateway_ref' => 'RENEWAL_' . strtoupper(uniqid()),
                    'paid_at' => now(),
                ]);

                // Extend subscription
                $subscription->update([
                    'expires_at' => $subscription->expires_at->addMonth(),
                ]);

                Cache::forget("cafe_subscription_{$cafe->id}");

                return true;
            } catch (\Exception $e) {
                Log::error('Subscription renewal failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);

                // Mark subscription as expired if payment fails
                $subscription->update([
                    'status' => 'expired',
                    'auto_renew' => false,
                ]);

                return false;
            }
        });
    }

    /**
     * Get subscriptions expiring soon
     */
    public function getExpiringSubscriptions(int $daysAhead = 1)
    {
        $targetDate = now()->addDays($daysAhead);

        return CafeSubscription::query()
            ->with(['cafe', 'plan', 'paymentMethod'])
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->whereDate('expires_at', '<=', $targetDate->toDateString())
            ->whereDate('expires_at', '>', now()->toDateString())
            ->get();
    }

    /**
     * Format subscription data with calculated fields
     */
    private function formatSubscriptionData(CafeSubscription $subscription): array
    {
        $expiresAt = Carbon::parse($subscription->expires_at);
        $daysLeft = max(0, now()->diffInDays($expiresAt, false));

        return [
            'id' => $subscription->id,
            'plan' => [
                'id' => $subscription->plan->id,
                'name' => $subscription->plan->name,
                'slug' => $subscription->plan->slug,
                'price' => $subscription->plan->price,
                'currency' => $subscription->plan->currency,
                'features' => $subscription->plan->features,
                'max_bookings' => $subscription->plan->max_bookings,
                'has_analytics' => $subscription->plan->has_analytics,
                'has_branding' => $subscription->plan->has_branding,
                'has_priority_support' => $subscription->plan->has_priority_support,
            ],
            'status' => $subscription->status,
            'starts_at' => $subscription->starts_at->toIso8601String(),
            'expires_at' => $subscription->expires_at->toIso8601String(),
            'days_left' => (int) $daysLeft,
            'auto_renew' => $subscription->auto_renew,
            'payment_method' => $subscription->paymentMethod ? [
                'id' => $subscription->paymentMethod->id,
                'type' => $subscription->paymentMethod->type,
                'card_last_four' => $subscription->paymentMethod->card_last_four,
            ] : null,
        ];
    }
}
