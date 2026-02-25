<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendBookingCancelledNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(BookingCancelled $event): void
    {
        $booking = $event->booking->load(['branch.cafe']);
        
        $event->booking->user->notify(
            new BookingCancelledNotification($booking)
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(BookingCancelled $event, \Throwable $exception): void
    {
        Log::error('Failed to send booking cancelled notification', [
            'booking_id' => $event->booking->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
