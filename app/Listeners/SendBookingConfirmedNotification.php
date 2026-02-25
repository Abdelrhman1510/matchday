<?php

namespace App\Listeners;

use App\Events\BookingCreated;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendBookingConfirmedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(BookingCreated $event): void
    {
        $booking = $event->booking->load(['branch.cafe']);
        
        $event->booking->user->notify(
            new BookingConfirmedNotification($booking)
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(BookingCreated $event, \Throwable $exception): void
    {
        Log::error('Failed to send booking confirmed notification', [
            'booking_id' => $event->booking->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
