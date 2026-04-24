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

        // 1. Upcoming → Live: kick_off time on match_date has passed
        $matchesToGoLive = GameMatch::where('status', 'upcoming')
            ->whereNotNull('kick_off')
            ->whereRaw('TIMESTAMP(match_date, kick_off) <= ?', [now()])
            ->get();

        foreach ($matchesToGoLive as $match) {
            $match->update(['status' => 'live', 'is_live' => true]);
            $this->line("  ✓ Match #{$match->id} set to LIVE");
            $updatedToLive++;

            Cache::forget("match_{$match->id}");
            Cache::forget("branch_overview_{$match->branch_id}");
        }

        // 2. Live → Finished: kick_off + duration has passed
        $matchesToFinish = GameMatch::where('status', 'live')
            ->whereNotNull('kick_off')
            ->whereRaw('TIMESTAMP(match_date, kick_off) <= ?', [now()->subMinutes(90)])
            ->get()
            ->filter(function ($match) {
                $kickOff = Carbon::parse($match->match_date->format('Y-m-d') . ' ' . $match->kick_off);
                return $kickOff->copy()->addMinutes($match->duration_minutes ?? 90)->isPast();
            });

        foreach ($matchesToFinish as $match) {
            $match->update(['status' => 'finished', 'is_live' => false]);
            $this->line("  ✓ Match #{$match->id} set to FINISHED");
            $updatedToFinished++;

            Cache::forget("match_{$match->id}");
            Cache::forget("branch_overview_{$match->branch_id}");
            Cache::forget("occupancy_realtime_{$match->branch_id}");
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
