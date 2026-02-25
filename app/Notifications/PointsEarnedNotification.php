<?php

namespace App\Notifications;

use App\Traits\SendsPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PointsEarnedNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsPushNotifications;

    protected int $points;
    protected string $reason;
    protected ?int $bookingId;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(int $points, string $reason = 'Points earned', ?int $bookingId = null)
    {
        $this->points = $points;
        $this->reason = $reason;
        $this->bookingId = $bookingId;
    }

    public function via(object $notifiable): array
    {
        if (!$this->isNotificationEnabled($notifiable, 'promotions')) {
            return [];
        }

        return [\App\Channels\DatabaseNotificationChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $additionalData = [
            'type' => 'points_earned',
            'points' => $this->points,
        ];

        if ($this->bookingId) {
            $additionalData['booking_id'] = $this->bookingId;
        }

        $notificationData = [
            'title' => '⭐ Points Earned!',
            'body' => "You've earned {$this->points} points! {$this->reason}",
            'data' => $additionalData,
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
