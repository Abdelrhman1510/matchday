<?php

namespace App\Console\Commands;

use App\Models\CafeSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Mark past-due subscriptions as expired and reset cafe subscription plan';

    public function handle(): int
    {
        $this->info('Expiring stale subscriptions...');

        // Find all subscriptions that are still marked active/cancelled
        // but their expires_at has already passed
        $stale = CafeSubscription::query()
            ->with('cafe')
            ->whereIn('status', ['active', 'cancelled'])
            ->where('expires_at', '<=', now())
            ->get();

        if ($stale->isEmpty()) {
            $this->info('No stale subscriptions found.');
            return 0;
        }

        $this->info("Found {$stale->count()} stale subscription(s).");

        $count = 0;

        foreach ($stale as $subscription) {
            DB::transaction(function () use ($subscription, &$count) {
                try {
                    // Mark the subscription expired
                    $subscription->update(['status' => 'expired']);

                    // Reset the cafe's subscription_plan field so it no longer
                    // appears to be on a paid plan
                    $cafe = $subscription->cafe;
                    if ($cafe) {
                        // Only reset if this was the cafe's current plan
                        // (guard against multiple old records)
                        $hasActiveSub = $cafe->subscriptions()
                            ->where('status', 'active')
                            ->where('expires_at', '>', now())
                            ->exists();

                        if (!$hasActiveSub) {
                            $cafe->update(['subscription_plan' => null]);
                        }
                    }

                    $count++;
                    $this->line("  ✓ Expired subscription #{$subscription->id} for cafe: {$cafe?->name}");
                } catch (\Throwable $e) {
                    Log::error('Failed to expire subscription', [
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("  ✗ Failed to expire subscription #{$subscription->id}: {$e->getMessage()}");
                }
            });
        }

        $this->newLine();
        $this->info("Done. Expired {$count} subscription(s).");

        return 0;
    }
}
