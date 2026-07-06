<?php

namespace App\Notifications;

use App\Traits\SendsPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsPushNotifications;

    protected string $userName;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(string $userName)
    {
        $this->userName = $userName;
    }

    public function via(object $notifiable): array
    {
        // Welcome notifications always sent regardless of settings
        return [\App\Channels\DatabaseNotificationChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $notificationData = [
            'title' => 'Welcome to MatchDay! ⚽',
            'title_ar' => 'مرحبًا بك في ماتش داي! ⚽',
            'body' => "Hi {$this->userName}! We're excited to have you here. Start exploring cafes and booking your perfect match day experience!",
            'body_ar' => "مرحبًا {$this->userName}! يسعدنا وجودك معنا. ابدأ باستكشاف المقاهي واحجز تجربة مباراتك المثالية!",
            'data' => [
                'type' => 'welcome',
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
