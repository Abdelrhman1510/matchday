<?php

namespace App\Notifications;

use App\Models\Cafe;
use App\Models\Branch;
use App\Traits\SendsPushNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StaffInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsPushNotifications;

    protected Cafe $cafe;
    protected ?Branch $branch;
    protected string $role;
    protected string $signedUrl;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(Cafe $cafe, string $role, string $signedUrl, ?Branch $branch = null)
    {
        $this->cafe = $cafe;
        $this->role = $role;
        $this->signedUrl = $signedUrl;
        $this->branch = $branch;
    }

    public function via(object $notifiable): array
    {
        // Staff invitations always sent regardless of settings
        return [\App\Channels\DatabaseNotificationChannel::class];
    }

    public function toDatabase(object $notifiable): array
    {
        $branchInfo = $this->branch ? " at {$this->branch->name}" : '';
        
        $additionalData = [
            'type' => 'staff_invitation',
            'cafe_id' => $this->cafe->id,
            'role' => $this->role,
            'invitation_url' => $this->signedUrl,
        ];

        if ($this->branch) {
            $additionalData['branch_id'] = $this->branch->id;
        }

        $notificationData = [
            'title' => 'Staff Invitation',
            'body' => "You've been invited to join {$this->cafe->name}{$branchInfo} as {$this->role}.",
            'data' => $additionalData,
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
