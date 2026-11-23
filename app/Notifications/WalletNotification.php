<?php

namespace App\Notifications;

use App\Models\WithdrawalRequest;
use App\Traits\SendsPushNotifications;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Wallet events for the cafe owner. Financial, so user mute settings don't apply. */
class WalletNotification extends Notification
{
    use SendsPushNotifications;

    private const TEXT = [
        'wallet_earning_available' => ['Your earnings are ready to withdraw', 'أرباحك جاهزة للسحب', 'New earnings are now available in your wallet.', 'أصبحت أرباح جديدة متاحة في محفظتك.'],
        'bank_account_changed' => ['Payout account changed', 'تم تغيير حساب السحب', 'Your payout bank account was changed and is awaiting verification. If this was not you, contact support now.', 'تم تغيير حسابك البنكي للسحب وهو بانتظار التحقق. إذا لم تكن أنت، تواصل مع الدعم فورًا.'],
        'bank_account_verified' => ['Bank account verified', 'تم التحقق من الحساب البنكي', 'You can now withdraw your earnings.', 'يمكنك الآن سحب أرباحك.'],
        'bank_account_rejected' => ['Bank account needs attention', 'الحساب البنكي يحتاج إلى مراجعة', 'Your bank account could not be verified.', 'تعذّر التحقق من حسابك البنكي.'],
        'withdrawal_approved' => ['Withdrawal approved', 'تمت الموافقة على السحب', 'Your withdrawal was approved.', 'تمت الموافقة على طلب السحب.'],
        'withdrawal_paid' => ['Withdrawal sent to your bank', 'تم إرسال السحب إلى بنكك', 'Your withdrawal is on its way to your bank.', 'تم إرسال المبلغ إلى حسابك البنكي.'],
        'withdrawal_rejected' => ['Withdrawal rejected', 'تم رفض السحب', 'Your withdrawal was rejected and the amount is back in your wallet.', 'تم رفض طلب السحب وأُعيد المبلغ إلى محفظتك.'],
        'withdrawal_failed' => ['Withdrawal failed, amount returned to wallet', 'فشل السحب وأُعيد المبلغ إلى المحفظة', 'Your withdrawal could not be completed.', 'تعذّر إتمام عملية السحب.'],
    ];

    public function __construct(
        private string $eventType,
        private ?WithdrawalRequest $withdrawal = null,
        private array $context = [],
    ) {}

    public function via(object $notifiable): array
    {
        $channels = [\App\Channels\DatabaseNotificationChannel::class];
        if ($this->eventType === 'bank_account_changed' && !empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        [$title, $titleAr, $body, $bodyAr] = self::TEXT[$this->eventType];
        if (!empty($this->context['reason'])) {
            $body .= ' ' . $this->context['reason'];
            $bodyAr .= ' ' . $this->context['reason'];
        }

        $data = ['type' => $this->eventType];
        if ($this->withdrawal) {
            $data['withdrawal_id'] = $this->withdrawal->id;
        }

        $isArabic = ($notifiable->locale ?? null) === 'ar';
        $this->sendFcmPush($notifiable, [
            'title' => $isArabic ? $titleAr : $title,
            'body' => $isArabic ? $bodyAr : $body,
            'data' => $data,
        ]);

        return ['title' => $title, 'title_ar' => $titleAr, 'body' => $body, 'body_ar' => $bodyAr, 'type' => $this->eventType, 'data' => $data];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$title, $titleAr, $body, $bodyAr] = self::TEXT[$this->eventType];

        return (new MailMessage)->subject("{$title} | {$titleAr}")->line($body)->line($bodyAr);
    }

    public function toArray(object $notifiable): array
    {
        return ['type' => $this->eventType, 'withdrawal_id' => $this->withdrawal?->id];
    }
}
