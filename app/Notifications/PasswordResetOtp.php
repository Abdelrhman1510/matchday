<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetOtp extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $otp;
    protected string $userName;
    protected string $userEmail;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $otp, string $userName, string $userEmail)
    {
        $this->otp = $otp;
        $this->userName = $userName;
        $this->userEmail = $userEmail;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Password Reset OTP - MatchDay')
            ->greeting('Hello ' . $this->userName . '!')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->line('Your password reset OTP is:')
            ->line('**' . $this->otp . '**')
            ->line('This OTP will expire in 10 minutes.')
            ->line('If you did not request a password reset, please ignore this email. No further action is required.')
            ->salutation('Best regards, The MatchDay Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'otp' => $this->otp,
            'expires_at' => now()->addMinutes(10),
        ];
    }
}
