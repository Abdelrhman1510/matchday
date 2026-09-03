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
     * Upgrade or change subscription plan.
     *
     * The plan price is recomputed here (never trusted from the client) and the
     * stored card token is actually charged via the gateway. The subscription is
     * only changed once the charge succeeds.
     */
    public function upgradePlan(Cafe $cafe, int $planId, int $paymentMethodId): array
    {
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

        $amount = (float) $plan->price; // server-side amount
        $requiresCharge = $amount > 0;

        if ($requiresCharge && !$paymentMethod) {
            throw new \Exception('A payment method is required for this plan.');
        }

        // Record the attempt, then charge outside the DB transaction so a failed
        // attempt leaves an auditable record instead of rolling away.
        $payment = Payment::create([
            'user_id' => $cafe->owner_id,
            'payment_method_id' => $paymentMethodId ?: null,
            'amount' => $amount,
            'currency' => $plan->currency,
            'status' => 'pending',
            'type' => 'subscription',
            'description' => "Subscription payment for {$plan->name} plan",
        ]);

        if ($requiresCharge) {
            $result = app(\App\Contracts\PaymentGatewayInterface::class)->charge($payment, $paymentMethod);

            if (!($result['success'] ?? false)) {
                // 3DS on a stored token is rare; the app has no webview here, so we
                // record it pending and surface a clear message rather than upgrade.
                $payment->update([
                    'status' => !empty($result['requires_action']) ? 'pending' : 'failed',
                    'gateway_ref' => $result['gateway_ref'] ?? null,
                    'description' => $result['message'] ?? 'Payment failed',
                ]);
                throw new \Exception($result['message'] ?? 'Payment failed. Please try another card.');
            }

            $payment->update([
                'status' => 'paid',
                'gateway_ref' => $result['gateway_ref'] ?? null,
                'paid_at' => now(),
            ]);
        } else {
            $payment->update(['status' => 'paid', 'paid_at' => now()]);
        }

        return DB::transaction(function () use ($cafe, $plan, $paymentMethodId, $payment) {
            $existingSubscription = $cafe->subscriptions()
                ->where('status', 'active')
                ->where('expires_at', '>', now())
                ->first();

            if ($existingSubscription) {
                $existingSubscription->update([
                    'plan_id' => $plan->id,
                    'starts_at' => now(),
                    'expires_at' => now()->addMonth(),
                    'payment_method_id' => $paymentMethodId ?: $existingSubscription->payment_method_id,
                    'auto_renew' => true,
                ]);
                $subscription = $existingSubscription;
            } else {
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

            if (!$payment->subscription_id) {
                $payment->update(['subscription_id' => $subscription->id]);
            }

            $cafe->update(['subscription_plan' => $plan->slug]);
            Cache::forget("cafe_subscription_{$cafe->id}");

            return [
                'subscription' => $this->formatSubscriptionData($subscription->load(['plan', 'paymentMethod'])),
                'payment' => $payment->fresh(),
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

        $plan = $subscription->plan;
        $cafe = $subscription->cafe;

        $payment = Payment::create([
            'user_id' => $cafe->owner_id,
            'subscription_id' => $subscription->id,
            'payment_method_id' => $subscription->payment_method_id,
            'amount' => $plan->price,
            'currency' => $plan->currency,
            'status' => 'pending',
            'type' => 'subscription',
            'description' => "Automatic renewal for {$plan->name} plan",
        ]);

        $result = app(\App\Contracts\PaymentGatewayInterface::class)->charge($payment, $subscription->paymentMethod);

        if ($result['success'] ?? false) {
            $payment->update(['status' => 'paid', 'gateway_ref' => $result['gateway_ref'] ?? null, 'paid_at' => now()]);
            $subscription->update(['expires_at' => $subscription->expires_at->addMonth()]);
            Cache::forget("cafe_subscription_{$cafe->id}");
            return true;
        }

        // 3DS: no app is in the loop for a server-scheduled renewal, so it can't be
        // completed here. Flag the subscription past_due (keeps it from silently
        // lapsing) and prompt the customer to re-confirm in-app — do NOT expire it.
        if (!empty($result['requires_action'])) {
            $payment->update(['status' => 'pending', 'gateway_ref' => $result['gateway_ref'] ?? null, 'description' => 'Renewal needs card re-confirmation (3DS)']);
            $subscription->update(['status' => 'past_due']);
            Cache::forget("cafe_subscription_{$cafe->id}");
            Log::warning('Subscription renewal needs 3DS re-confirmation', ['subscription_id' => $subscription->id]);
            // ponytail: notify the owner to re-confirm in-app once a notification channel exists.
            return false;
        }

        // Hard decline — record it and stop auto-renew.
        $payment->update(['status' => 'failed', 'gateway_ref' => $result['gateway_ref'] ?? null, 'description' => $result['message'] ?? 'Renewal declined']);
        Log::error('Subscription renewal failed', ['subscription_id' => $subscription->id, 'message' => $result['message'] ?? null]);
        $subscription->update(['status' => 'expired', 'auto_renew' => false]);
        return false;
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
                'name_ar' => $subscription->plan->name_ar,
                'slug' => $subscription->plan->slug,
                'price' => $subscription->plan->price,
                'currency' => $subscription->plan->currency,
                'currency_ar' => \App\Support\Currency::arabicName($subscription->plan->currency),
                'features' => $subscription->plan->features,
                'features_ar' => $subscription->plan->features_ar,
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
