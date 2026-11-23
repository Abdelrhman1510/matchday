<?php

namespace App\Services\Wallet;

use App\Exceptions\MoyasarApiException;
use App\Exceptions\WalletException;
use App\Jobs\SendWithdrawalPayout;
use App\Models\BankAccount;
use App\Models\Cafe;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WithdrawalEvent;
use App\Models\WithdrawalRequest;
use App\Notifications\WalletNotification;
use App\Services\Payment\MoyasarClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WithdrawalService
{
    public function __construct(
        private WalletService $wallet,
        private FeeSettingsService $fees,
        private MoyasarClient $moyasar,
    ) {}

    // ─── Rules ──────────────────────────────────────────────────────────

    /** The first rule that blocks a withdrawal, or null. */
    public function blockedCode(Wallet $wallet, ?BankAccount $bank): ?string
    {
        $rules = $this->fees->block('withdrawals');
        $available = $this->wallet->balances($wallet)['available'];

        return match (true) {
            !$rules['enabled'] => 'withdrawals_disabled',
            !$bank => 'bank_account_missing',
            $bank->status !== 'verified' => 'bank_account_unverified',
            $rules['one_open_request'] && $this->hasOpenRequest($wallet) => 'open_request_exists',
            $available < 0 => 'negative_balance',
            $available < (float) $rules['min_amount'] => 'amount_below_min',
            default => null,
        };
    }

    public function overview(Cafe $cafe): array
    {
        $wallet = $this->wallet->walletFor($cafe);
        $bank = BankAccount::where('cafe_id', $cafe->id)->first();
        $rules = $this->fees->block('withdrawals');
        $code = $this->blockedCode($wallet, $bank);

        return [
            'currency' => $wallet->currency,
            'balances' => $this->wallet->balances($wallet),
            'withdrawal_rules' => [
                'min_amount' => (float) $rules['min_amount'],
                'max_amount' => (float) $rules['max_amount'],
                'fee' => (float) $rules['fee'],
                'can_withdraw' => $code === null,
                'blocked_reason' => $code ? __("wallet.errors.{$code}", ['min' => $rules['min_amount'], 'max' => $rules['max_amount']]) : null,
            ],
            'has_bank_account' => $bank !== null,
        ];
    }

    private function hasOpenRequest(Wallet $wallet): bool
    {
        return WithdrawalRequest::where('wallet_id', $wallet->id)
            ->whereIn('status', WalletService::OPEN_WITHDRAWALS)
            ->exists();
    }

    private function assertCanWithdraw(Wallet $wallet, ?BankAccount $bank, float $amount): array
    {
        $rules = $this->fees->block('withdrawals');
        $params = ['min' => $rules['min_amount'], 'max' => $rules['max_amount']];

        if (!$rules['enabled']) {
            throw new WalletException('withdrawals_disabled');
        }
        if (!$bank) {
            throw new WalletException('bank_account_missing');
        }
        if ($bank->status !== 'verified') {
            throw new WalletException('bank_account_unverified');
        }
        if ($rules['one_open_request'] && $this->hasOpenRequest($wallet)) {
            throw new WalletException('open_request_exists');
        }
        if ($amount < (float) $rules['min_amount']) {
            throw new WalletException('amount_below_min', $params);
        }
        if ((float) $rules['max_amount'] > 0 && $amount > (float) $rules['max_amount']) {
            throw new WalletException('amount_above_max', $params);
        }
        if ($amount > $this->wallet->balances($wallet)['available']) {
            throw new WalletException('insufficient_balance');
        }

        return $rules;
    }

    // ─── Owner actions ──────────────────────────────────────────────────

    public function preview(Cafe $cafe, float $amount): array
    {
        $amount = round($amount, 2);
        $wallet = $this->wallet->walletFor($cafe);
        $bank = BankAccount::where('cafe_id', $cafe->id)->first();
        $rules = $this->assertCanWithdraw($wallet, $bank, $amount);
        $fee = round((float) $rules['fee'], 2);

        return [
            'amount' => $amount,
            'fee' => $fee,
            'net_amount' => round($amount - $fee, 2),
            'currency' => $wallet->currency,
            'bank_account' => ['bank_name' => $bank->bank_name, 'iban_masked' => $bank->iban_masked],
        ];
    }

    public function request(Cafe $cafe, float $amount, string $idempotencyKey, User $user): WithdrawalRequest
    {
        $amount = round($amount, 2);
        $wallet = $this->wallet->walletFor($cafe);

        $request = DB::transaction(function () use ($cafe, $wallet, $amount, $idempotencyKey, $user) {
            $wallet = $this->wallet->lock($wallet);

            $existing = WithdrawalRequest::where('wallet_id', $wallet->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();
            if ($existing) {
                return $existing;
            }

            $bank = BankAccount::where('cafe_id', $cafe->id)->first();
            $rules = $this->assertCanWithdraw($wallet, $bank, $amount);
            $fee = round((float) $rules['fee'], 2);

            $request = WithdrawalRequest::create([
                'wallet_id' => $wallet->id,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => round($amount - $fee, 2),
                'currency' => $wallet->currency,
                'status' => 'pending',
                'funds_reserved' => true,
                'idempotency_key' => $idempotencyKey,
                'bank_snapshot' => [
                    'bank_account_id' => $bank->id,
                    'account_holder_name' => $bank->account_holder_name,
                    'bank_name' => $bank->bank_name,
                    'bank_code' => $bank->bank_code,
                    'iban' => $bank->iban,
                    'iban_masked' => $bank->iban_masked,
                    'city' => $bank->city,
                    'mobile' => $bank->mobile,
                ],
            ]);
            $request->update(['reference' => sprintf('WD-%s-%06d', now()->format('Y'), $request->id)]);

            $this->writeDebits($request);
            $this->log($request, $user, 'requested', null, 'pending');

            return $request;
        });

        return $request->refresh(); // keeps wasRecentlyCreated for the 201/200 response
    }

    public function cancelByOwner(WithdrawalRequest $request, User $user): WithdrawalRequest
    {
        return DB::transaction(function () use ($request, $user) {
            $request = $this->locked($request);
            if ($request->status !== 'pending') {
                throw new WalletException('cannot_cancel');
            }
            $this->reverseFunds($request);
            $request->update(['status' => 'cancelled']);
            $this->log($request, $user, 'cancelled', 'pending', 'cancelled');

            return $request;
        });
    }

    // ─── Admin actions ──────────────────────────────────────────────────

    public function approveAndSend(WithdrawalRequest $request, User $admin): WithdrawalRequest
    {
        if ($this->fees->block('withdrawals')['payout_mode'] !== 'moyasar') {
            throw new WalletException('payout_mode_manual');
        }
        $this->assertPayoutsReady();

        $request = DB::transaction(function () use ($request, $admin) {
            $request = $this->locked($request);
            $this->assertStatus($request, ['pending']);
            $this->assertBankStillVerified($request);

            $request->update([
                'status' => 'approved',
                'payout_uuid' => (string) Str::uuid(), // saved before any call to Moyasar
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);
            $this->log($request, $admin, 'approved', 'pending', 'approved');

            return $request;
        });

        $this->notify($request, 'withdrawal_approved');
        $this->dispatchPayout($request);

        return $request->fresh();
    }

    public function reject(WithdrawalRequest $request, User $admin, string $reason): WithdrawalRequest
    {
        $this->assertNoLivePayout($request->fresh());

        $request = DB::transaction(function () use ($request, $admin, $reason) {
            $request = $this->locked($request);
            $this->assertStatus($request, ['pending', 'approved']);
            $from = $request->status;
            $this->reverseFunds($request);
            $request->update([
                'status' => 'rejected', 'rejection_reason' => $reason,
                'reviewed_by' => $admin->id, 'reviewed_at' => now(),
            ]);
            $this->log($request, $admin, 'rejected', $from, 'rejected', $reason);

            return $request;
        });

        $this->notify($request, 'withdrawal_rejected', ['reason' => $reason]);

        return $request;
    }

    public function markPaidManually(WithdrawalRequest $request, User $admin, string $transferRef, string $transferDate, ?string $receiptPath = null): WithdrawalRequest
    {
        $this->assertNoLivePayout($request->fresh());

        $request = DB::transaction(function () use ($request, $admin, $transferRef, $transferDate, $receiptPath) {
            $request = $this->locked($request);
            $this->assertStatus($request, ['pending', 'approved', 'failed']);
            $from = $request->status;
            if ($from === 'pending') {
                $this->assertBankStillVerified($request);
            }
            $this->reReserveFunds($request);

            $request->update([
                'status' => 'paid', 'paid_at' => now(), 'payout_blocked' => false,
                'manual_transfer_ref' => $transferRef, 'manual_transfer_date' => $transferDate,
                'manual_receipt_path' => $receiptPath,
                'reviewed_by' => $admin->id, 'reviewed_at' => now(),
            ]);
            $this->log($request, $admin, 'marked_paid', $from, 'paid', $transferRef);

            return $request;
        });

        $this->notify($request, 'withdrawal_paid');

        return $request;
    }

    public function retry(WithdrawalRequest $request, User $admin): WithdrawalRequest
    {
        if ($this->fees->block('withdrawals')['payout_mode'] !== 'moyasar') {
            throw new WalletException('payout_mode_manual');
        }
        $this->assertPayoutsReady();
        $this->assertNoLivePayout($request->fresh());

        $request = DB::transaction(function () use ($request, $admin) {
            $request = $this->locked($request);
            $this->assertStatus($request, ['failed']);
            $this->assertBankStillVerified($request);
            $this->reReserveFunds($request);

            $request->update([
                'status' => 'approved',
                'payout_uuid' => (string) Str::uuid(), // a new attempt gets a new given_id
                'moyasar_payout_id' => null, 'moyasar_status' => null, 'moyasar_channel' => null,
                'moyasar_sequence_number' => null, 'moyasar_message' => null,
                'failure_reason' => null, 'payout_blocked' => false,
            ]);
            $this->log($request, $admin, 'retried', 'failed', 'approved');

            return $request;
        });

        $this->dispatchPayout($request);

        return $request->fresh();
    }

    /** The bank sent a "paid" transfer back later. */
    public function markReturned(WithdrawalRequest $request, User $admin, ?string $note = null): WithdrawalRequest
    {
        $request = DB::transaction(function () use ($request, $admin, $note) {
            $request = $this->locked($request);
            $this->assertStatus($request, ['paid']);
            $this->reverseFunds($request);
            $request->update(['status' => 'failed', 'failure_reason' => $note ?: 'Returned by bank']);
            $this->log($request, $admin, 'marked_returned', 'paid', 'failed', $note);

            return $request;
        });

        $this->notify($request, 'withdrawal_failed');

        return $request;
    }

    // ─── Moyasar payout ─────────────────────────────────────────────────

    public function sendPayout(int $requestId): void
    {
        if (!$this->payoutsReady()) {
            return;
        }

        $request = WithdrawalRequest::find($requestId);
        if (!$request || $request->status !== 'approved' || $request->payout_blocked || !$request->payout_uuid) {
            return;
        }

        try {
            $payout = $this->moyasar->createPayout($this->payoutBody($request));
        } catch (MoyasarApiException $e) {
            $this->handlePayoutError($request, $e);
            return;
        } catch (\Throwable $e) {
            // Timeout or network: the poll job resends the same given_id.
            Log::warning("Payout {$request->reference}: send failed, will retry: {$e->getMessage()}");
            return;
        }

        $this->applyPayout($request, $payout);
    }

    private function handlePayoutError(WithdrawalRequest $request, MoyasarApiException $e): void
    {
        if ($e->isTransient() || in_array($e->status, [401, 403], true)) {
            // A bad key or a Moyasar outage must not fail the owner's request.
            Log::error("Payout {$request->reference}: Moyasar {$e->status}: {$e->getMessage()}");
            return;
        }

        if ($e->type === 'used_given_id') {
            Log::critical("Payout {$request->reference}: used_given_id. Details changed on a retry; blocked for manual review.");
            $request->update(['payout_blocked' => true, 'moyasar_message' => $e->getMessage()]);
            return;
        }

        $this->failRequest($request->id, $e->getMessage(), null);
    }

    private function payoutBody(WithdrawalRequest $request): array
    {
        $bank = $request->bank_snapshot;
        $cafeId = (string) $request->wallet->cafe_id;

        return [
            'source_id' => config('services.moyasar.payout_source_id'),
            'given_id' => $request->payout_uuid,
            'amount' => (int) round(((float) $request->net_amount) * 100),
            'purpose' => config('services.moyasar.payout_purpose', 'payment_to_merchant'),
            'destination' => [
                'type' => 'bank',
                'iban' => $bank['iban'],
                'name' => $bank['account_holder_name'],
                'mobile' => $bank['mobile'],
                'country' => 'SA',
                'city' => $bank['city'],
            ],
            'comment' => "Shaj3 payout {$request->reference}",
            'metadata' => [
                'withdrawal_id' => (string) $request->id,
                'cafe_id' => $cafeId,
                'reference' => (string) $request->reference,
            ],
        ];
    }

    public function applyPayout(WithdrawalRequest $request, array $payout): void
    {
        $status = $payout['status'] ?? null;

        $event = DB::transaction(function () use ($request, $payout, $status) {
            $request = $this->locked($request);
            $request->fill([
                'moyasar_payout_id' => $payout['id'] ?? $request->moyasar_payout_id,
                'moyasar_status' => $status,
                'moyasar_channel' => $payout['channel'] ?? $request->moyasar_channel,
                'moyasar_sequence_number' => $payout['sequence_number'] ?? $request->moyasar_sequence_number,
                'moyasar_message' => $payout['message'] ?? $request->moyasar_message,
                'moyasar_checked_at' => now(),
            ]);

            $from = $request->status;
            if ($from === 'approved') {
                $request->status = 'processing';
                $this->log($request, null, 'payout_sent', 'approved', 'processing');
                $from = 'processing';
            }

            if ($status === 'paid' && $from === 'processing') {
                $request->fill(['status' => 'paid', 'paid_at' => now()])->save();
                $this->log($request, null, 'payout_paid', 'processing', 'paid');
                return 'withdrawal_paid';
            }

            if (in_array($status, ['failed', 'canceled', 'returned'], true) && in_array($from, ['processing', 'paid'], true)) {
                $this->reverseFunds($request);
                $request->fill([
                    'status' => 'failed',
                    'failure_reason' => ($payout['failure_reason'] ?? null) ?: ($payout['message'] ?? $status),
                ])->save();
                $this->log($request, null, "payout_{$status}", $from, 'failed', $request->failure_reason);
                return 'withdrawal_failed';
            }

            $request->save();

            return null;
        });

        if ($event) {
            $this->notify($request->fresh(), $event);
        }
    }

    private function failRequest(int $id, string $message, ?string $reason): void
    {
        $request = DB::transaction(function () use ($id, $message, $reason) {
            $request = WithdrawalRequest::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($request->status !== 'approved') {
                return null;
            }
            $this->reverseFunds($request);
            $request->update(['status' => 'failed', 'failure_reason' => $reason ?: $message, 'moyasar_message' => $message]);
            $this->log($request, null, 'payout_rejected', 'approved', 'failed', $message);

            return $request;
        });

        if ($request) {
            $this->notify($request, 'withdrawal_failed');
        }
    }

    /** No webhooks exist for payouts: this job is the only way to learn the result. */
    public function pollOpenPayouts(): array
    {
        $checked = 0;

        // Approved but never acknowledged by Moyasar: resend with the same given_id.
        WithdrawalRequest::where('status', 'approved')
            ->where('payout_blocked', false)
            ->whereNotNull('payout_uuid')
            ->where('updated_at', '<', now()->subMinutes(2))
            ->pluck('id')
            ->each(fn ($id) => $this->sendPayout($id));

        foreach (WithdrawalRequest::where('status', 'processing')->get() as $request) {
            $checked++;
            try {
                $payout = $this->moyasar->getPayout($request->moyasar_payout_id ?: $request->payout_uuid);
            } catch (\Throwable $e) {
                Log::warning("Payout {$request->reference}: status check failed: {$e->getMessage()}");
                continue;
            }

            if ($payout === null) {
                // Never created on Moyasar's side: safe to send with the same given_id.
                $request->update(['status' => 'approved']);
                $this->sendPayout($request->id);
                continue;
            }

            $this->applyPayout($request, $payout);

            if ($request->fresh()->status === 'processing' && $request->reviewed_at?->lt(now()->subHours(48))) {
                Log::critical("Payout {$request->reference} has been processing for over 48 hours.");
            }
        }

        return ['checked' => $checked];
    }

    /** Nightly: every recent Moyasar payout must match a request, with the same status. */
    public function reconcile(): array
    {
        $issues = [];
        $seen = 0;
        $page = 1;
        $cutoff = now()->subDays(7);

        do {
            $response = $this->moyasar->listPayouts($page);
            $stop = false;
            foreach ($response['payouts'] ?? [] as $payout) {
                if (!empty($payout['created_at']) && \Carbon\Carbon::parse($payout['created_at'])->lt($cutoff)) {
                    $stop = true;
                    continue;
                }
                $seen++;

                $request = WithdrawalRequest::where('moyasar_payout_id', $payout['id'])
                    ->orWhere('payout_uuid', $payout['id'])
                    ->first();
                if (!$request) {
                    $issues[] = "Moyasar payout {$payout['id']} has no matching withdrawal";
                    continue;
                }

                if (($payout['status'] ?? null) === 'returned' && $request->status === 'paid') {
                    $this->applyPayout($request, $payout);
                    $issues[] = "{$request->reference} was returned by the bank; balance restored";
                } elseif (($payout['status'] ?? null) === 'paid' && !in_array($request->status, ['paid', 'processing'], true)) {
                    $issues[] = "{$request->reference} is {$request->status} but Moyasar says paid";
                } elseif (in_array($payout['status'] ?? null, ['failed', 'canceled'], true) && $request->status === 'paid'
                    && $request->moyasar_payout_id === $payout['id']) {
                    $issues[] = "{$request->reference} is paid but Moyasar says {$payout['status']}";
                }
            }
            $next = $response['meta']['next_page'] ?? null;
            $page = $next ?: 0;
        } while ($page && !$stop);

        foreach ($issues as $issue) {
            Log::critical("Payout reconciliation: {$issue}");
        }

        return ['checked' => $seen, 'issues' => $issues];
    }

    // ─── Internals ──────────────────────────────────────────────────────

    private function locked(WithdrawalRequest $request): WithdrawalRequest
    {
        return WithdrawalRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
    }

    private function assertStatus(WithdrawalRequest $request, array $allowed): void
    {
        if (!in_array($request->status, $allowed, true)) {
            throw new WalletException('invalid_transition');
        }
    }

    private function payoutsReady(): bool
    {
        return config('services.moyasar.payouts_enabled') && config('services.moyasar.payout_source_id');
    }

    private function assertPayoutsReady(): void
    {
        if (!$this->payoutsReady()) {
            throw new WalletException('payout_not_configured');
        }
    }

    private function assertBankStillVerified(WithdrawalRequest $request): void
    {
        $bank = BankAccount::where('cafe_id', $request->wallet->cafe_id)->first();
        if (!$bank || $bank->status !== 'verified' || $bank->iban !== ($request->bank_snapshot['iban'] ?? null)) {
            throw new WalletException('bank_snapshot_mismatch');
        }
    }

    /** Before anything that ends a request: make sure no money is on its way. */
    private function assertNoLivePayout(?WithdrawalRequest $request): void
    {
        if (!$request || !$request->payout_uuid || !in_array($request->status, ['approved', 'failed'], true)) {
            return;
        }

        try {
            $payout = $this->moyasar->getPayout($request->moyasar_payout_id ?: $request->payout_uuid);
        } catch (\Throwable $e) {
            throw new WalletException('payout_status_unknown');
        }

        if ($payout !== null && !in_array($payout['status'] ?? null, ['failed', 'canceled', 'returned'], true)) {
            throw new WalletException('payout_in_flight');
        }
    }

    private function writeDebits(WithdrawalRequest $request): void
    {
        $base = [
            'wallet_id' => $request->wallet_id, 'direction' => 'debit', 'status' => 'available',
            'withdrawal_id' => $request->id, 'reference' => $request->reference, 'available_at' => now(),
        ];
        WalletTransaction::create($base + [
            'type' => 'withdrawal', 'amount' => $request->net_amount,
            'title' => "Withdrawal {$request->reference}", 'title_ar' => "سحب {$request->reference}",
        ]);
        if ((float) $request->fee > 0) {
            WalletTransaction::create($base + [
                'type' => 'withdrawal_fee', 'amount' => $request->fee,
                'title' => "Withdrawal fee {$request->reference}", 'title_ar' => "رسوم سحب {$request->reference}",
            ]);
        }
    }

    /** Returns the whole reserved amount (net + fee), exactly once. */
    private function reverseFunds(WithdrawalRequest $request): void
    {
        if (!$request->funds_reserved) {
            return;
        }

        WalletTransaction::create([
            'wallet_id' => $request->wallet_id, 'type' => 'withdrawal_reversal', 'direction' => 'credit',
            'amount' => round((float) $request->net_amount + (float) $request->fee, 2), 'status' => 'available',
            'withdrawal_id' => $request->id, 'title' => "Withdrawal returned {$request->reference}",
            'title_ar' => "إرجاع سحب {$request->reference}", 'reference' => $request->reference,
            'available_at' => now(),
        ]);
        $request->funds_reserved = false;
        $request->save();
    }

    /** A failed request already gave the money back, so it must be taken again first. */
    private function reReserveFunds(WithdrawalRequest $request): void
    {
        if ($request->funds_reserved) {
            return;
        }

        $wallet = $this->wallet->lock($request->wallet);
        if ((float) $request->amount > $this->wallet->balances($wallet)['available']) {
            throw new WalletException('insufficient_balance');
        }

        $this->writeDebits($request);
        $request->funds_reserved = true;
        $request->save();
    }

    private function dispatchPayout(WithdrawalRequest $request): void
    {
        try {
            SendWithdrawalPayout::dispatch($request->id);
        } catch (\Throwable $e) {
            // Stays approved; the poll job resends with the same given_id.
            Log::error("Payout {$request->reference}: dispatch failed: {$e->getMessage()}");
        }
    }

    private function log(WithdrawalRequest $request, ?User $user, string $action, ?string $from, ?string $to, ?string $note = null): void
    {
        WithdrawalEvent::create([
            'withdrawal_id' => $request->id, 'user_id' => $user?->id, 'action' => $action,
            'from_status' => $from, 'to_status' => $to, 'note' => $note, 'created_at' => now(),
        ]);
    }

    private function notify(WithdrawalRequest $request, string $type, array $context = []): void
    {
        try {
            $request->wallet->cafe?->owner?->notify(new WalletNotification($type, $request, $context));
        } catch (\Throwable $e) {
            Log::warning("Wallet notification {$type} failed for {$request->reference}: {$e->getMessage()}");
        }
    }
}
