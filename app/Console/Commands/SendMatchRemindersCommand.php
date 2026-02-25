<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\GameMatch;
use App\Notifications\MatchReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

class SendMatchRemindersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matchday:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send match reminder notifications to users with bookings for matches starting within 1 hour';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking for upcoming matches requiring reminders...');

        // Find matches with kick_off within 1 hour that haven't been reminded yet
        $oneHourFromNow = now()->addHour();
        $now = now();

        $matches = GameMatch::with(['branch.cafe', 'homeTeam', 'awayTeam'])
            ->where('kick_off', '<=', $oneHourFromNow)
            ->where('kick_off', '>', $now)
            ->where('is_published', true)
            ->whereIn('status', ['upcoming', 'live'])
            ->get();

        if ($matches->isEmpty()) {
            $this->info('✓ No matches requiring reminders.');
            return Command::SUCCESS;
        }

        $this->info("Found {$matches->count()} match(es) to process.");

        $totalNotificationsSent = 0;

        foreach ($matches as $match) {
            $cacheKey = "match_reminder_sent_{$match->id}";

            // Skip if reminder already sent
            if (Cache::has($cacheKey)) {
                $this->line("  Skipped Match #{$match->id} - Reminder already sent");
                continue;
            }

            // Get all confirmed bookings for this match
            $bookings = Booking::with('user')
                ->where('match_id', $match->id)
                ->where('status', 'confirmed')
                ->get();

            if ($bookings->isEmpty()) {
                $this->line("  Skipped Match #{$match->id} - No confirmed bookings");
                continue;
            }

            $users = $bookings->pluck('user')->filter()->unique('id');

            if ($users->isEmpty()) {
                $this->line("  Skipped Match #{$match->id} - No users found");
                continue;
            }

            // Send notifications
            Notification::send($users, new MatchReminderNotification($match));

            // Mark as reminded (cache for 2 hours to prevent duplicate sends)
            Cache::put($cacheKey, true, now()->addHours(2));

            $this->info("  ✓ Match #{$match->id} ({$match->homeTeam->name} vs {$match->awayTeam->name}) - Sent to {$users->count()} user(s)");
            $totalNotificationsSent += $users->count();
        }

        $this->newLine();
        $this->info("Reminder process completed:");
        $this->info("  Matches processed: {$matches->count()}");
        $this->info("  Notifications sent: {$totalNotificationsSent}");

        return Command::SUCCESS;
    }
}
