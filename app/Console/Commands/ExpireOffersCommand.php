<?php

namespace App\Console\Commands;

use App\Models\Offer;
use Illuminate\Console\Command;

class ExpireOffersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'offers:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire offers that have passed their valid_until date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting offers expiration check...');

        // Find all active offers that have passed their valid_until date
        $expiredCount = Offer::where('status', 'active')
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now()->toDateString())
            ->update(['status' => 'expired', 'is_active' => false]);

        // Also expire offers where is_active is true but valid_until is past
        $expiredActive = Offer::where('is_active', true)
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now()->toDateString())
            ->update(['status' => 'expired', 'is_active' => false]);

        $total = $expiredCount + $expiredActive;

        if ($total > 0) {
            $this->info("✓ {$total} offer(s) expired successfully.");
        } else {
            $this->info('✓ No offers to expire.');
        }

        return Command::SUCCESS;
    }
}
