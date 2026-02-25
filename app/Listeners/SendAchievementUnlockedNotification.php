<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Notifications\AchievementUnlockedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendAchievementUnlockedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(AchievementUnlocked $event): void
    {
        $event->user->notify(
            new AchievementUnlockedNotification($event->achievement)
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(AchievementUnlocked $event, \Throwable $exception): void
    {
        Log::error('Failed to send achievement unlocked notification', [
            'user_id' => $event->user->id,
            'achievement_id' => $event->achievement->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
