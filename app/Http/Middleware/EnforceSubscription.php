<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionEnforcementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSubscription
{
    public function __construct(
        protected SubscriptionEnforcementService $enforcement
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  string|null  $feature  Optional feature flag to check (e.g., 'has_analytics')
     */
    public function handle(Request $request, Closure $next, ?string $feature = null): Response
    {
        $cafe = $request->user()?->ownedCafes()?->first();

        if (!$cafe) {
            return response()->json([
                'success' => false,
                'message' => 'No cafe found for this user.',
            ], 404);
        }

        // If a specific feature is requested, check it
        if ($feature) {
            if (!$this->enforcement->hasFeature($cafe, $feature)) {
                $planName = $cafe->subscriptions()
                    ->where('status', 'active')
                    ->with('plan')
                    ->first()?->plan?->name ?? 'current';

                return response()->json([
                    'success' => false,
                    'message' => "Your {$planName} plan does not include this feature. Please upgrade your subscription.",
                    'feature' => $feature,
                ], 403);
            }

            // Feature available — check grace period for warning headers
            $response = $next($request);
            return $this->addGracePeriodHeaders($cafe, $request, $response);
        }

        // No specific feature — just check subscription is active or in grace period
        $hasActive = $cafe->subscriptions()
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->exists();

        if ($hasActive) {
            return $next($request);
        }

        // Check grace period
        if ($this->enforcement->isInGracePeriod($cafe)) {
            $response = $next($request);
            return $this->addGracePeriodHeaders($cafe, $request, $response);
        }

        // Expired past grace
        return response()->json([
            'success' => false,
            'message' => 'Your subscription has expired. Please renew to continue using this feature.',
        ], 403);
    }

    /**
     * Add grace period warning headers and body data to the response.
     */
    private function addGracePeriodHeaders($cafe, Request $request, Response $response): Response
    {
        if (!$this->enforcement->isInGracePeriod($cafe)) {
            return $response;
        }

        $daysLeft = $this->enforcement->getGracePeriodDaysLeft($cafe);

        $response->headers->set('X-Subscription-Grace-Period', "{$daysLeft} days left");

        // If JSON response, inject subscription_warning
        if ($request->expectsJson() && $response->headers->get('Content-Type') === 'application/json') {
            $content = json_decode($response->getContent(), true);
            if (is_array($content)) {
                $content['subscription_warning'] = "Your subscription has expired. You have {$daysLeft} day(s) left in your grace period.";
                $response->setContent(json_encode($content));
            }
        }

        return $response;
    }
}
