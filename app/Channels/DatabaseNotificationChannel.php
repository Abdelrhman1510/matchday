<?php

namespace App\Channels;

use App\Events\NewNotification;
use App\Models\Notification as NotificationModel;
use Illuminate\Notifications\Notification;

class DatabaseNotificationChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        $data = $notification->toDatabase($notifiable);

        $record = NotificationModel::create([
            'user_id' => $notifiable->id,
            'type' => get_class($notification),
            'title' => $data['title'] ?? 'Notification',
            'body' => $data['body'] ?? '',
            'data' => $data['data'] ?? $data,
            'read_at' => null,
        ]);

        // Broadcast notification in real-time via Reverb
        try {
            broadcast(new NewNotification($notifiable->id, [
                'id' => $record->id,
                'type' => $data['type'] ?? class_basename($notification),
                'title' => $record->title,
                'body' => $record->body,
                'data' => $record->data,
                'created_at' => $record->created_at->toIso8601String(),
            ]));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcasting notification failed', [
                'user_id' => $notifiable->id,
                'notification' => class_basename($notification),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
