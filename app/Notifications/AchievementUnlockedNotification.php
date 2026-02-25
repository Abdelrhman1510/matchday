<?php

namespace App\Notifications;

use App\Models\Achievement;
use App\Traits\SendsPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AchievementUnlockedNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsPushNotifications;

    protected Achievement $achievement;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(Achievement $achievement)
    {
        $this->achievement = $achievement;
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
        $notificationData = [
            'title' => '🎉 Achievement Unlocked!',
            'body' => "You've unlocked the '{$this->achievement->name}' achievement!",
            'data' => [
                'type' => 'achievement_unlocked',
                'achievement_id' => $this->achievement->id,
                'points' => $this->achievement->points,
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
