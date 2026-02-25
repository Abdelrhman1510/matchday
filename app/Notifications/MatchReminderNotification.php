<?php

namespace App\Notifications;

use App\Models\GameMatch;
use App\Traits\SendsPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MatchReminderNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsPushNotifications;

    protected GameMatch $match;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(GameMatch $match)
    {
        $this->match = $match;
    }

    public function via(object $notifiable): array
    {
        if (!$this->isNotificationEnabled($notifiable, 'match_updates')) {
            return [];
        }

        return [\App\Channels\DatabaseNotificationChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $homeTeam = $this->match->homeTeam->name;
        $awayTeam = $this->match->awayTeam->name;
        
        $notificationData = [
            'title' => 'Match Starting Soon!',
            'body' => "{$homeTeam} vs {$awayTeam} starts in 1 hour. Don't miss it!",
            'data' => [
                'type' => 'match_reminder',
                'match_id' => $this->match->id,
                'home_team_id' => $this->match->home_team_id,
                'away_team_id' => $this->match->away_team_id,
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
