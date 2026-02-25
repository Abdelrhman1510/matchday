<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Match as MatchModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateOccupancyCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matchday:update-occupancy-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh occupancy cache for branches with live matches';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Updating occupancy cache...');

        // Get all branches with live matches
        $branchesWithLiveMatches = Branch::whereHas('matches', function ($query) {
            $query->where('status', 'live')
                ->where('is_published', true);
        })->get();

        if ($branchesWithLiveMatches->isEmpty()) {
            $this->info('✓ No branches with live matches.');
            return Command::SUCCESS;
        }

        $this->info("Found {$branchesWithLiveMatches->count()} branch(es) with live matches.");

        $updatedCount = 0;

        foreach ($branchesWithLiveMatches as $branch) {
            try {
                // Calculate real-time occupancy
                $totalSeats = $branch->seating_capacity ?? DB::table('seats')
                    ->whereIn('section_id', function ($query) use ($branch) {
                        $query->select('id')
                            ->from('seating_sections')
                            ->where('branch_id', $branch->id);
                    })
                    ->count();

                $occupiedSeats = DB::table('booking_seats')
                    ->join('bookings', 'booking_seats.booking_id', '=', 'bookings.id')
                    ->join('matches', 'bookings.match_id', '=', 'matches.id')
                    ->where('matches.branch_id', $branch->id)
                    ->where('matches.status', 'live')
                    ->where('bookings.status', 'confirmed')
                    ->distinct('booking_seats.seat_id')
                    ->count('booking_seats.seat_id');

                $occupancyPercentage = $totalSeats > 0 
                    ? round(($occupiedSeats / $totalSeats) * 100, 2) 
                    : 0;

                $occupancyData = [
                    'branch_id' => $branch->id,
                    'total_seats' => $totalSeats,
                    'occupied_seats' => $occupiedSeats,
                    'available_seats' => $totalSeats - $occupiedSeats,
                    'occupancy_percentage' => $occupancyPercentage,
                    'status' => $occupancyPercentage >= 90 ? 'full' : ($occupancyPercentage >= 70 ? 'busy' : 'available'),
                    'updated_at' => now()->toIso8601String(),
                ];

                // Cache for 2 minutes
                Cache::put("occupancy_realtime_{$branch->id}", $occupancyData, now()->addMinutes(2));

                $this->line("  ✓ Branch #{$branch->id} ({$branch->name}): {$occupancyPercentage}% occupied");
                $updatedCount++;

            } catch (\Exception $e) {
                $this->error("  ✗ Branch #{$branch->id} failed: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Occupancy cache update completed:");
        $this->info("  Branches updated: {$updatedCount}");

        return Command::SUCCESS;
    }
}
