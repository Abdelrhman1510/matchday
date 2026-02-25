<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait SendsPushNotifications
{
    /**
     * Send FCM push notification if user has device_token
     */
    protected function sendFcmPush($notifiable, array $data): void
    {
        if (!$notifiable->device_token || !config('services.fcm.server_key')) {
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('services.fcm.server_key'),
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $notifiable->device_token,
                'notification' => [
                    'title' => $data['title'],
                    'body' => $data['body'],
                    'sound' => 'default',
                ],
                'data' => $data['data'] ?? [],
                'priority' => 'high',
            ]);

            if (!$response->successful()) {
                Log::warning('FCM push failed', [
                    'user_id' => $notifiable->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('FCM push exception', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if user has enabled this notification type
     */
    protected function isNotificationEnabled($notifiable, string $settingKey): bool
    {
        $settings = $notifiable->notification_settings ?? [];
        
        // Default to true if setting doesn't exist
        return $settings[$settingKey] ?? true;
    }
}
