<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Cafe;
use App\Models\GameMatch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * ExploreTestSeeder
 *
 * Ensures all sections of the Explore API return rich data:
 *   ✅ featured_cafes   — premium cafes with avg_rating >= 4.0
 *   ✅ nearby_cafes     — branches within 20 km of a given coordinate
 *   ✅ trending_cafes   — cafes with bookings in the last week
 *   ✅ matches_today    — live + upcoming matches dated TODAY
 *   ✅ popular_matches  — future matches with recent booking counts
 *   ✅ active_offers    — already handled by OfferSeeder
 *
 * Safe to run multiple times (uses updateOrCreate / upsert patterns).
 * Run after the main DatabaseSeeder has already populated core data.
 */
class ExploreTestSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔍 Seeding Explore test data...');

        // ── 1. Ensure we have premium high-rated cafes (featured_cafes) ──────
        Cafe::whereIn('name', ['Champions Sports Lounge', 'Sky Sports Lounge'])
            ->update([
                'is_premium'  => true,
                'is_featured' => true,
                'avg_rating'  => 4.9,
            ]);

        $this->command->line('   ✓ Featured cafes updated');

        // ── 2. Fix TODAY's matches ────────────────────────────────────────────
        // The MatchSeeder creates live matches for today, but kick_off times
        // may mismatch. Patch the two live matches to be today.
        $today = now()->toDateString();

        // Live match 1 — Man Utd vs Liverpool (already today in MatchSeeder)
        GameMatch::where('status', 'live')
            ->update([
                'match_date'       => $today,
                'is_published'     => true,
                'booking_opens_at' => now()->subDays(7),
            ]);

        // Add 2 upcoming matches for TODAY so the UI "Matches Today" section fills
        $branches = Branch::where('is_open', true)->take(3)->get();
        $manCity  = Team::where('name', 'Manchester City')->first();
        $arsenal  = Team::where('name', 'Arsenal')->first();
        $chelsea  = Team::where('name', 'Chelsea')->first();
        $liverpool = Team::where('name', 'Liverpool')->first();

        if ($branches->count() >= 2 && $manCity && $arsenal) {
            // Only create if no upcoming match already exists for today
            $existingToday = GameMatch::where('status', 'upcoming')
                ->where('match_date', $today)
                ->count();

            if ($existingToday === 0) {
                GameMatch::create([
                    'branch_id'        => $branches[0]->id,
                    'home_team_id'     => $manCity->id,
                    'away_team_id'     => $arsenal->id,
                    'league'           => 'Premier League',
                    'match_date'       => $today,
                    'kick_off'         => now()->addHours(2)->format('H:i'),
                    'status'           => 'upcoming',
                    'home_score'       => null,
                    'away_score'       => null,
                    'seats_available'  => 20,
                    'price_per_seat'   => 75.00,
                    'duration_minutes' => 90,
                    'total_revenue'    => 0,
                    'booking_opens_at' => now()->subDays(5),
                    'booking_closes_at'=> now()->addHours(1)->addMinutes(45),
                    'is_published'     => true,
                ]);

                GameMatch::create([
                    'branch_id'        => $branches[1]->id,
                    'home_team_id'     => $chelsea->id,
                    'away_team_id'     => $liverpool->id,
                    'league'           => 'Premier League',
                    'match_date'       => $today,
                    'kick_off'         => now()->addHours(4)->format('H:i'),
                    'status'           => 'upcoming',
                    'home_score'       => null,
                    'away_score'       => null,
                    'seats_available'  => 18,
                    'price_per_seat'   => 60.00,
                    'duration_minutes' => 90,
                    'total_revenue'    => 0,
                    'booking_opens_at' => now()->subDays(5),
                    'booking_closes_at'=> now()->addHours(3)->addMinutes(45),
                    'is_published'     => true,
                ]);

                $this->command->line('   ✓ 2 upcoming matches added for today');
            } else {
                $this->command->line('   ✓ Today\'s upcoming matches already exist ('.$existingToday.')');
            }
        }

        // ── 3. Generate bookings this week to populate trending_cafes ─────────
        $fans = User::where('role', 'fan')->take(5)->get();

        // Get future/today published matches that can be booked
        $bookableMatches = GameMatch::where('is_published', true)
            ->whereIn('status', ['upcoming', 'live'])
            ->where('match_date', '>=', $today)
            ->take(5)
            ->get();

        if ($fans->count() > 0 && $bookableMatches->count() > 0) {
            $created = 0;
            foreach ($bookableMatches as $match) {
                foreach ($fans->take(3) as $fan) {
                    $alreadyBooked = Booking::where('user_id', $fan->id)
                        ->where('match_id', $match->id)
                        ->exists();

                    if (!$alreadyBooked) {
                        Booking::create([
                            'booking_code' => strtoupper(substr(md5(uniqid()), 0, 10)),
                            'user_id'      => $fan->id,
                            'match_id'     => $match->id,
                            'branch_id'    => $match->branch_id,
                            'status'       => 'confirmed',
                            'guests_count' => 1,
                            'subtotal'     => $match->price_per_seat,
                            'service_fee'  => 0,
                            'total_amount' => $match->price_per_seat,
                            'currency'     => 'SAR',
                            'created_at'   => now()->subDays(rand(0, 6)),
                            'updated_at'   => now(),
                        ]);
                        $created++;
                    }
                }
            }
            $this->command->line("   ✓ {$created} bookings created this week (for trending)");
        }

        // ── 4. Clear explore caches so fresh data loads ───────────────────────
        Cache::flush();
        $this->command->line('   ✓ Cache flushed');

        // ── 5. Summary ────────────────────────────────────────────────────────
        $this->command->newLine();
        $this->command->info('📊 Explore Data Summary:');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->line('⭐ Featured cafes (premium, rating≥4.9): ' . Cafe::where('is_premium', true)->where('avg_rating', '>=', 4.0)->count());
        $this->command->line('📅 Matches today (live):    ' . GameMatch::where('status', 'live')->where('match_date', $today)->count());
        $this->command->line('📅 Matches today (upcoming): ' . GameMatch::where('status', 'upcoming')->where('match_date', $today)->count());
        $this->command->line('📈 Bookings this week:      ' . Booking::where('created_at', '>=', now()->subWeek())->count());
        $this->command->line('🏠 Open branches:           ' . Branch::where('is_open', true)->count());
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();
        $this->command->info('💡 TIP: Pass ?lat=24.698&lng=46.686 to see nearby_cafes');
        $this->command->info('💡 TIP: Trending cafes require bookings within the past 7 days ✓');
    }
}
