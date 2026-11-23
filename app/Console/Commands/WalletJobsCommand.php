<?php

namespace App\Console\Commands;

use App\Models\PlatformSetting;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Notifications\WalletNotification;
use App\Services\Wallet\BookingPaymentService;
use App\Services\Wallet\WalletService;
use App\Services\Wallet\WithdrawalService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/** The scheduled money jobs, one command with a task argument. */
class WalletJobsCommand extends Command
{
    protected $signature = 'wallet:run {task : tick|expire-bookings|promote-earnings|poll-payouts|reconcile|notify-earnings}';

    protected $description = 'Run a scheduled wallet job';

    public function handle(BookingPaymentService $bookings, WalletService $wallet, WithdrawalService $withdrawals): int
    {
        switch ($this->argument('task')) {
            case 'tick':
                $this->tick();
                break;
            case 'expire-bookings':
                $this->info('Expired ' . $bookings->expireUnpaid() . ' unpaid booking(s).');
                break;
            case 'promote-earnings':
                $this->info('Promoted ' . $wallet->promoteDue() . ' ledger row(s).');
                break;
            case 'poll-payouts':
                $this->info('Checked ' . $withdrawals->pollOpenPayouts()['checked'] . ' payout(s).');
                break;
            case 'reconcile':
                return $this->reconcile($withdrawals);
            case 'notify-earnings':
                $this->notifyEarnings();
                break;
            default:
                $this->error('Unknown task.');
                return Command::INVALID;
        }

        return Command::SUCCESS;
    }

    /** Called every minute. Runs only wallet jobs; one failing never stops the others. */
    private function tick(): void
    {
        $now = now('Asia/Riyadh');
        $tasks = ['expire-bookings'];
        if ($now->minute % 5 === 0) {
            $tasks[] = 'promote-earnings';
        }
        if ($now->minute % 10 === 0) {
            $tasks[] = 'poll-payouts';
        }
        if ($now->hour >= 2 && $this->firstRunToday('reconcile', $now->toDateString())) {
            $tasks[] = 'reconcile';
        }
        if ($now->hour >= 9 && $this->firstRunToday('notify-earnings', $now->toDateString())) {
            $tasks[] = 'notify-earnings';
        }

        foreach ($tasks as $task) {
            try {
                $this->call('wallet:run', ['task' => $task]);
            } catch (\Throwable $e) {
                Log::error("Wallet job {$task} failed: {$e->getMessage()}");
            }
        }
    }

    /** Stored in the database so a container restart can't repeat a daily job. */
    private function firstRunToday(string $task, string $date): bool
    {
        $key = "wallet_tick.{$task}";
        if (PlatformSetting::where('key', $key)->value('value') === $date) {
            return false;
        }
        PlatformSetting::updateOrCreate(['key' => $key], ['value' => $date, 'type' => 'string', 'group' => 'wallet_jobs']);

        return true;
    }

    private function reconcile(WithdrawalService $withdrawals): int
    {
        if (!config('services.moyasar.payout_source_id')) {
            $this->info('Payouts are not configured; nothing to reconcile.');
            return Command::SUCCESS;
        }

        try {
            $result = $withdrawals->reconcile();
        } catch (\Throwable $e) {
            Log::error("Payout reconciliation failed: {$e->getMessage()}");
            $this->error('Reconciliation failed.');
            return Command::FAILURE;
        }

        Log::info('Payout reconciliation finished', ['checked' => $result['checked'], 'issues' => count($result['issues'])]);
        $this->info("Checked {$result['checked']} payout(s), " . count($result['issues']) . ' issue(s).');

        return Command::SUCCESS;
    }

    private function notifyEarnings(): void
    {
        $walletIds = WalletTransaction::where('type', 'booking_earning')
            ->where('status', 'available')
            ->whereBetween('available_at', [now()->subDay(), now()])
            ->distinct()
            ->pluck('wallet_id');

        foreach (Wallet::with('cafe.owner')->whereIn('id', $walletIds)->get() as $wallet) {
            try {
                $wallet->cafe?->owner?->notify(new WalletNotification('wallet_earning_available'));
            } catch (\Throwable $e) {
                Log::warning("Earnings digest failed for wallet {$wallet->id}: {$e->getMessage()}");
            }
        }
        $this->info("Notified {$walletIds->count()} owner(s).");
    }
}
