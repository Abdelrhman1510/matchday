<?php

namespace App\Listeners;

use App\Events\PointsEarned;
use App\Notifications\PointsEarnedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendPointsEarnedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PointsEarned $event): void
    {
        $bookingId = $event->transaction->booking_id ?? null;
        
        $event->user->notify(
            new PointsEarnedNotification(
                $event->points,
                $event->description,
                $bookingId
            )
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(PointsEarned $event, \Throwable $exception): void
    {
        Log::error('Failed to send points earned notification', [
            'user_id' => $event->user->id,
            'points' => $event->points,
            'error' => $exception->getMessage(),
        ]);
    }
}
