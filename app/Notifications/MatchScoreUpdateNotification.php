<?php

namespace App\Notifications;

use App\Models\GameMatch;
use App\Traits\SendsPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MatchScoreUpdateNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsPushNotifications;

    protected GameMatch $match;
    protected string $event;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(GameMatch $match, string $event = 'Score updated')
    {
        $this->match = $match;
        $this->event = $event;
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
        $homeScore = $this->match->home_score ?? 0;
        $awayScore = $this->match->away_score ?? 0;
        
        $notificationData = [
            'title' => 'Match Update!',
            'body' => "{$homeTeam} {$homeScore} - {$awayScore} {$awayTeam}. {$this->event}",
            'data' => [
                'type' => 'match_score_update',
                'match_id' => $this->match->id,
                'home_team_id' => $this->match->home_team_id,
                'away_team_id' => $this->match->away_team_id,
                'home_score' => $homeScore,
                'away_score' => $awayScore,
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
