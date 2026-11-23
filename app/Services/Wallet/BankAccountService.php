<?php

namespace App\Services\Wallet;

use App\Models\BankAccount;
use App\Models\Cafe;
use App\Models\User;
use App\Notifications\WalletNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BankAccountService
{
    // Verify against SAMA's current list before go-live.
    public const BANKS = [
        '05' => 'Alinma', '10' => 'Saudi National Bank', '15' => 'Bank AlBilad', '20' => 'Riyad Bank',
        '30' => 'Arab National Bank', '45' => 'SAB', '55' => 'Banque Saudi Fransi', '60' => 'Bank AlJazira',
        '65' => 'Saudi Investment Bank', '80' => 'Al Rajhi Bank',
    ];

    public static function normalizeIban(string $iban): string
    {
        return strtoupper(preg_replace('/\s+/u', '', $iban));
    }

    /** Saudi format plus the ISO 13616 mod-97 checksum. */
    public static function isValidIban(string $iban): bool
    {
        if (!preg_match('/^SA\d{22}$/', $iban)) {
            return false;
        }

        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        $digits = preg_replace_callback('/[A-Z]/', fn ($m) => (string) (ord($m[0]) - 55), $rearranged);

        $remainder = 0;
        foreach (str_split($digits) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder === 1;
    }

    public static function normalizeMobile(string $mobile): ?string
    {
        $mobile = preg_replace('/[\s-]+/', '', $mobile);

        return preg_match('/^(?:\+?966|0)(5\d{8})$/', $mobile, $m) ? '0' . $m[1] : null;
    }

    /** Any create or change must be verified again, so a stolen session can't redirect payouts. */
    public function save(Cafe $cafe, array $data): BankAccount
    {
        $iban = self::normalizeIban($data['iban']);
        if (!self::isValidIban($iban)) {
            throw ValidationException::withMessages(['iban' => [__('wallet.errors.invalid_iban')]]);
        }
        $mobile = self::normalizeMobile($data['mobile']);
        if (!$mobile) {
            throw ValidationException::withMessages(['mobile' => [__('wallet.errors.invalid_mobile')]]);
        }

        $code = substr($iban, 4, 2);
        $account = BankAccount::firstOrNew(['cafe_id' => $cafe->id]);
        $account->fill([
            'account_holder_name' => trim($data['account_holder_name']),
            'iban' => $iban,
            'iban_last4' => substr($iban, -4),
            'bank_code' => $code,
            'bank_name' => self::BANKS[$code] ?? "Bank {$code}",
            'city' => trim($data['city']),
            'mobile' => $mobile,
            'status' => 'pending_verification',
            'rejection_reason' => null,
            'verified_by' => null,
            'verified_at' => null,
        ])->save();

        $this->notifyOwner($cafe, 'bank_account_changed');

        return $account;
    }

    public function verify(BankAccount $account, User $by): void
    {
        $account->update([
            'status' => 'verified', 'rejection_reason' => null, 'verified_by' => $by->id, 'verified_at' => now(),
        ]);
        $this->notifyOwner($account->cafe, 'bank_account_verified');
    }

    public function reject(BankAccount $account, string $reason): void
    {
        $account->update([
            'status' => 'rejected', 'rejection_reason' => $reason, 'verified_by' => null, 'verified_at' => null,
        ]);
        $this->notifyOwner($account->cafe, 'bank_account_rejected', ['reason' => $reason]);
    }

    private function notifyOwner(Cafe $cafe, string $type, array $context = []): void
    {
        try {
            $cafe->owner?->notify(new WalletNotification($type, null, $context));
        } catch (\Throwable $e) {
            Log::warning("Wallet notification {$type} failed for cafe {$cafe->id}: {$e->getMessage()}");
        }
    }
}
