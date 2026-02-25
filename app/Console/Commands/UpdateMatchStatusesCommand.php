<?php

namespace App\Console\Commands;

use App\Models\GameMatch;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class UpdateMatchStatusesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matchday:update-match-statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-update match statuses based on kick_off time and duration';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Checking match statuses...');

        $updatedToLive = 0;
        $updatedToFinished = 0;

        // 1. Update matches to 'live' if kick_off has passed and status is 'upcoming'
        $matchesToGoLive = GameMatch::where('status', 'upcoming')
            ->where('is_published', true)
            ->where('kick_off', '<=', now())
            ->get();

        foreach ($matchesToGoLive as $match) {
            $match->update(['status' => 'live']);
            $this->line("  ✓ Match #{$match->id} set to LIVE");
            $updatedToLive++;

            // Clear relevant caches
            Cache::forget("match_{$match->id}");
            Cache::forget("branch_overview_{$match->branch_id}");
            Cache::tags(['home_feed', 'explore'])->flush();
        }

        // 2. Update matches to 'finished' if kick_off + duration has passed and status is 'live'
        $matchesToFinish = GameMatch::where('status', 'live')
            ->get()
            ->filter(function ($match) {
                // Calculate match end time
                $kickOff = Carbon::parse($match->kick_off);
                $matchEndTime = $kickOff->copy()->addMinutes($match->duration_minutes ?? 90);
                return $matchEndTime->isPast();
            });

        foreach ($matchesToFinish as $match) {
            $match->update(['status' => 'finished']);
            $this->line("  ✓ Match #{$match->id} set to FINISHED");
            $updatedToFinished++;

            // Clear relevant caches
            Cache::forget("match_{$match->id}");
            Cache::forget("branch_overview_{$match->branch_id}");
            Cache::forget("occupancy_realtime_{$match->branch_id}");
            Cache::tags(['home_feed', 'explore'])->flush();
        }

        $this->newLine();
        $this->info("Match status update completed:");
        $this->info("  Set to LIVE: {$updatedToLive}");
        $this->info("  Set to FINISHED: {$updatedToFinished}");

        if ($updatedToLive === 0 && $updatedToFinished === 0) {
            $this->info('  ✓ No matches required status updates.');
        }

        return Command::SUCCESS;
    }
}
