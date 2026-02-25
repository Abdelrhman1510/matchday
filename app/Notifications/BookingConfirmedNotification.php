<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Traits\SendsPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsPushNotifications;

    protected Booking $booking;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
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
            'title' => 'Booking Confirmed!',
            'body' => "Your booking at {$this->booking->branch->cafe->name} has been confirmed.",
            'data' => [
                'type' => 'booking_confirmed',
                'booking_id' => $this->booking->id,
                'branch_id' => $this->booking->branch_id,
                'cafe_id' => $this->booking->branch->cafe_id,
            ],
        ];

        // Send FCM push
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
