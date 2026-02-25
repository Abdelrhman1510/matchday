<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CleanupOtpsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matchday:cleanup-otps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired OTP cache keys';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting OTP cleanup process...');

        // OTP cache keys patterns used in the application:
        // - email_verification_otp:{email}
        // - password_reset_otp:{email}
        // - otp_attempts:{email}

        // Note: Laravel cache automatically removes expired keys when using file/redis drivers
        // This command serves as a manual cleanup for any orphaned keys
        
        // For Redis driver, we can explicitly scan and delete expired OTP keys
        if (config('cache.default') === 'redis') {
            $this->cleanupRedisOtps();
        } else {
            // For file/database drivers, expired items are automatically removed on access
            $this->info('Cache driver does not require manual OTP cleanup (auto-pruning enabled).');
        }

        $this->info('✓ OTP cleanup completed.');

        return Command::SUCCESS;
    }

    /**
     * Cleanup OTP keys from Redis cache
     */
    protected function cleanupRedisOtps(): void
    {
        try {
            $redis = Cache::getRedis();
            $connection = $redis->connection();
            
            $patterns = [
                'laravel_cache:email_verification_otp:*',
                'laravel_cache:password_reset_otp:*',
                'laravel_cache:otp_attempts:*',
            ];

            $deletedCount = 0;

            foreach ($patterns as $pattern) {
                $keys = $connection->keys($pattern);
                
                foreach ($keys as $key) {
                    // Check if key has TTL (not expired yet)
                    $ttl = $connection->ttl($key);
                    
                    // If TTL is -1 (no expiry) or -2 (already expired), clean it up
                    if ($ttl <= 0) {
                        $connection->del($key);
                        $deletedCount++;
                    }
                }
            }

            if ($deletedCount > 0) {
                $this->info("  ✓ Deleted {$deletedCount} expired OTP key(s) from Redis.");
            } else {
                $this->info('  ✓ No expired OTP keys found.');
            }
        } catch (\Exception $e) {
            $this->error("  ✗ Redis cleanup error: {$e->getMessage()}");
        }
    }
}
