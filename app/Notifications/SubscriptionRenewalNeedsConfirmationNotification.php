<?php

namespace App\Notifications;

use App\Models\CafeSubscription;
use App\Traits\SendsPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Auto-renewal hit a 3DS challenge, which can't be completed without the owner.
 * Billing-critical, so it is not gated on notification preferences.
 */
class SubscriptionRenewalNeedsConfirmationNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsPushNotifications;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(protected CafeSubscription $subscription) {}

    public function via(object $notifiable): array
    {
        return [\App\Channels\DatabaseNotificationChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $plan = $this->subscription->plan;

        $notificationData = [
            'title' => 'Confirm your subscription payment',
            'title_ar' => 'أكّد دفع اشتراكك',
            'body' => "Your bank needs you to confirm the renewal of your {$plan->name} plan. Open Subscription in the app to renew.",
            'body_ar' => "يحتاج البنك إلى تأكيدك لتجديد باقة {$plan->name}. افتح «الاشتراك» في التطبيق للتجديد.",
            'data' => [
                'type' => 'subscription_renewal_action_required',
                'subscription_id' => $this->subscription->id,
                'plan_id' => $plan->id,
            ],
        ];

        $this->sendFcmPush($notifiable, [
            'title' => $notificationData['title'],
            'body' => $notificationData['body'],
            'data' => $notificationData['data'],
        ]);

        return $notificationData;
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
