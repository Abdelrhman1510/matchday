<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Traits\SendsPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsPushNotifications;

    protected Booking $booking;
    protected string $reason;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(Booking $booking, string $reason = 'Cancelled by user')
    {
        $this->booking = $booking;
        $this->reason = $reason;
    }

    public function via(object $notifiable): array
    {
        if (!$this->isNotificationEnabled($notifiable, 'booking_reminders')) {
            return [];
        }

        return [\App\Channels\DatabaseNotificationChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $notificationData = [
            'title' => 'Booking Cancelled',
            'body' => "Your booking at {$this->booking->branch->cafe->name} has been cancelled. {$this->reason}",
            'data' => [
                'type' => 'booking_cancelled',
                'booking_id' => $this->booking->id,
                'branch_id' => $this->booking->branch_id,
                'cafe_id' => $this->booking->branch->cafe_id,
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
