<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionPlanResource;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * 1. GET /api/v1/cafe-admin/subscription
     * Get current subscription with features, expiry, days_left, auto_renew status
     */
    public function current(Request $request, $cafeId = null)
    {
        // Check permission
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        // If cafeId provided via route, check ownership
        if ($cafeId) {
            $cafe = \App\Models\Cafe::find($cafeId);
            if (!$cafe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cafe not found',
                ], 404);
            }
            if ($cafe->owner_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not own this cafe',
                ], 403);
            }
        } else {
            $cafe = $request->user()->ownedCafes()->first();
            if (!$cafe) {
                return response()->json([
                    'success' => false,
                    'message' => 'No cafe found for this owner',
                ], 404);
            }
        }

        $subscription = $this->subscriptionService->getCurrentSubscription($cafe);

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
                'data' => null,
            ], 200);
        }

        // Add current_period_end
        $subscription['current_period_end'] = $subscription['expires_at'] ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Current subscription retrieved successfully',
            'data' => $subscription,
        ]);
    }

    /**
     * 2. GET /api/v1/cafe-admin/subscription/plans
     * Get all subscription plans comparison (Public endpoint)
     */
    public function plans()
    {
        $plans = $this->subscriptionService->getAllPlans();

        return response()->json([
            'success' => true,
            'message' => 'Subscription plans retrieved successfully',
            'data' => SubscriptionPlanResource::collection($plans),
        ]);
    }

    /**
     * 3. POST /api/v1/cafe-admin/subscription/upgrade
     * Upgrade subscription plan
     */
    public function upgrade(Request $request)
    {
        // Check permission
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|integer|exists:subscription_plans,id',
            'payment_method_id' => 'sometimes|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        // Verify payment method exists and belongs to user (only if provided)
        $paymentMethod = null;
        if ($request->payment_method_id) {
            $paymentMethod = \App\Models\PaymentMethod::where('id', $request->payment_method_id)
                ->where('user_id', $request->user()->id)
                ->first();

            if (!$paymentMethod) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment method not found or does not belong to you',
                ], 404);
            }
        }

        // Verify plan exists and is active
        $plan = \App\Models\SubscriptionPlan::where('id', $request->plan_id)
            ->where('is_active', true)
            ->first();

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Subscription plan not found or is not available',
            ], 404);
        }

        // Check if already on this plan
        $currentSubscription = $cafe->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->first();

        if ($currentSubscription && $currentSubscription->plan_id == $request->plan_id) {
            return response()->json([
                'success' => false,
                'message' => 'You are already subscribed to this plan',
            ], 400);
        }

        try {
            $result = $this->subscriptionService->upgradePlan(
                $cafe,
                $request->plan_id,
                $request->payment_method_id ?? 0
            );

            return response()->json([
                'success' => true,
                'message' => 'Subscription upgraded successfully',
                'data' => [
                    'subscription' => $result['subscription'],
                    'payment' => [
                        'id' => $result['payment']->id,
                        'amount' => $result['payment']->amount,
                        'currency' => $result['payment']->currency,
                        'status' => $result['payment']->status,
                        'gateway_ref' => $result['payment']->gateway_ref,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to upgrade subscription: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 4. POST /api/v1/cafe-admin/subscription/cancel
     * Cancel subscription at period end (don't immediately expire)
     */
    public function cancel(Request $request)
    {
        // Check permission
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $success = $this->subscriptionService->cancelSubscription($cafe);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription to cancel',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription will be cancelled at the end of the billing period',
        ]);
    }

    /**
     * 5. PUT /api/v1/cafe-admin/subscription/auto-renew
     * Toggle auto-renew setting
     */
    public function toggleAutoRenew(Request $request)
    {
        // Check permission
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'auto_renew' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $cafe = $request->user()->ownedCafes()->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $success = $this->subscriptionService->toggleAutoRenew($cafe, $request->auto_renew);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Auto-renew setting updated successfully',
            'data' => [
                'auto_renew' => $request->auto_renew,
            ],
        ]);
    }

    /**
     * Downgrade subscription plan
     */
    public function downgrade(Request $request)
    {
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $subscription = \App\Models\CafeSubscription::where('cafe_id', $cafe->id)
            ->where('status', 'active')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found',
            ], 404);
        }

        $subscription->update([
            'scheduled_plan_id' => $request->plan_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription downgrade scheduled',
        ]);
    }

    /**
     * Resume cancelled subscription
     */
    public function resume(Request $request)
    {
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $subscription = \App\Models\CafeSubscription::where('cafe_id', $cafe->id)
            ->where('status', 'cancelled')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'No cancelled subscription found',
            ], 404);
        }

        $subscription->update(['status' => 'active']);

        return response()->json([
            'success' => true,
            'message' => 'Subscription resumed successfully',
        ]);
    }

    /**
     * Get billing history
     */
    public function billingHistory(Request $request)
    {
        if (!$request->user()->can('manage-subscription')) {
            return response()->json([
                'success' => false,
                'message' => 'Permission denied',
            ], 403);
        }

        $cafe = $request->user()->ownedCafes()->first();
        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this owner',
            ], 404);
        }

        $subscription = \App\Models\CafeSubscription::where('cafe_id', $cafe->id)->first();
        
        $payments = $subscription
            ? \App\Models\Payment::where('subscription_id', $subscription->id)
                ->orderBy('created_at', 'desc')
                ->get()
            : collect();

        return response()->json([
            'success' => true,
            'message' => 'Billing history retrieved',
            'data' => $payments->map(function ($p) {
                return [
                    'id' => $p->id,
                    'amount' => $p->amount,
                    'status' => $p->status,
                    'created_at' => $p->created_at,
                    'invoice_url' => null,
                ];
            }),
        ]);
    }

    /**
     * Handle payment failure webhook
     */
    public function handlePaymentFailed(Request $request)
    {
        $subscription = \App\Models\CafeSubscription::find($request->subscription_id);

        if ($subscription) {
            $subscription->update(['status' => 'past_due']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment failure processed',
        ]);
    }
}
