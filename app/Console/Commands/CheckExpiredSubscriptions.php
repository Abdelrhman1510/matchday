<?php

namespace App\Console\Commands;

use App\Models\CafeSubscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckExpiredSubscriptions extends Command
{
    protected $signature = 'subscriptions:check-expired';

    protected $description = 'Check for active subscriptions that have expired and mark them as expired';

    public function handle(): int
    {
        $this->info('Checking for expired subscriptions...');

        $expired = CafeSubscription::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired subscriptions found.');
            return 0;
        }

        $count = 0;

        foreach ($expired as $subscription) {
            try {
                $subscription->update(['status' => 'expired']);
                $count++;

                Log::info('Subscription marked as expired', [
                    'subscription_id' => $subscription->id,
                    'cafe_id' => $subscription->cafe_id,
                    'expired_at' => $subscription->expires_at,
                ]);

                $this->line("  ✓ Expired subscription #{$subscription->id} (cafe_id: {$subscription->cafe_id})");
            } catch (\Throwable $e) {
                Log::error('Failed to expire subscription', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ Failed to expire subscription #{$subscription->id}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done. Marked {$count} subscription(s) as expired.");

        return 0;
    }
}
