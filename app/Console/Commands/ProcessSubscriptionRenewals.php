<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ProcessSubscriptionRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process subscription renewals for subscriptions expiring within 1 day with auto-renew enabled';

    protected SubscriptionService $subscriptionService;

    /**
     * Create a new command instance.
     */
    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting subscription renewal process...');

        // Get subscriptions expiring within 1 day with auto_renew enabled
        $subscriptions = $this->subscriptionService->getExpiringSubscriptions(1);

        if ($subscriptions->isEmpty()) {
            $this->info('No subscriptions to renew.');
            return 0;
        }

        $this->info("Found {$subscriptions->count()} subscription(s) to process.");

        $successCount = 0;
        $failureCount = 0;

        foreach ($subscriptions as $subscription) {
            $cafe = $subscription->cafe;
            $plan = $subscription->plan;

            $this->line("Processing: Cafe #{$cafe->id} - {$cafe->name} ({$plan->name} plan)");

            $success = $this->subscriptionService->processRenewal($subscription);

            if ($success) {
                $this->info("  ✓ Renewal successful");
                $successCount++;
            } else {
                $this->error("  ✗ Renewal failed");
                $failureCount++;
            }
        }

        $this->newLine();
        $this->info("Renewal process completed:");
        $this->info("  Successful: {$successCount}");
        $this->info("  Failed: {$failureCount}");

        return 0;
    }
}
