<?php

namespace Database\Seeders;

use App\Models\GameMatch;
use App\Models\Branch;
use App\Models\Team;
use Illuminate\Database\Seeder;

class MatchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();
        $teams = Team::all();

        // Get specific teams for popular matches
        $manUtd = Team::where('name', 'Manchester United')->first();
        $liverpool = Team::where('name', 'Liverpool')->first();
        $chelsea = Team::where('name', 'Chelsea')->first();
        $arsenal = Team::where('name', 'Arsenal')->first();
        $manCity = Team::where('name', 'Manchester City')->first();
        $barcelona = Team::where('name', 'Barcelona')->first();
        $realMadrid = Team::where('name', 'Real Madrid')->first();
        $psg = Team::where('name', 'Paris Saint-Germain')->first();
        $bayern = Team::where('name', 'Bayern Munich')->first();
        $juventus = Team::where('name', 'Juventus')->first();

        // Live Matches (2)
        GameMatch::create([
            'branch_id' => $branches[0]->id,
            'home_team_id' => $manUtd->id,
            'away_team_id' => $liverpool->id,
            'league' => 'Premier League',
            'match_date' => now()->toDateString(),
            'kick_off' => now()->subMinutes(35)->format('H:i'),
            'home_score' => 1,
            'away_score' => 1,
            'status' => 'live',
            'seats_available' => 18,
            'price_per_seat' => 45.00,
            'duration_minutes' => 90,
            'total_revenue' => 270.00,
            'booking_opens_at' => now()->subDays(7),
            'booking_closes_at' => now()->subMinutes(40),
            'is_published' => true,
        ]);

        GameMatch::create([
            'branch_id' => $branches[1]->id,
            'home_team_id' => $barcelona->id,
            'away_team_id' => $realMadrid->id,
            'league' => 'La Liga',
            'match_date' => now()->toDateString(),
            'kick_off' => now()->subMinutes(20)->format('H:i'),
            'home_score' => 2,
            'away_score' => 0,
            'status' => 'live',
            'seats_available' => 15,
            'price_per_seat' => 45.00,
            'duration_minutes' => 90,
            'total_revenue' => 405.00,
            'booking_opens_at' => now()->subDays(7),
            'booking_closes_at' => now()->subMinutes(25),
            'is_published' => true,
        ]);

        // Upcoming Matches (4)
        GameMatch::create([
            'branch_id' => $branches[2]->id,
            'home_team_id' => $chelsea->id,
            'away_team_id' => $arsenal->id,
            'league' => 'Premier League',
            'match_date' => now()->addDays(1)->toDateString(),
            'kick_off' => '20:00',
            'status' => 'upcoming',
            'seats_available' => 24,
            'price_per_seat' => 40.00,
            'duration_minutes' => 90,
            'total_revenue' => 0.00,
            'booking_opens_at' => now(),
            'booking_closes_at' => now()->addDays(1)->setTimeFromTimeString('19:45'),
            'is_published' => true,
        ]);

        GameMatch::create([
            'branch_id' => $branches[3]->id,
            'home_team_id' => $manCity->id,
            'away_team_id' => $manUtd->id,
            'league' => 'Premier League',
            'match_date' => now()->addDays(2)->toDateString(),
            'kick_off' => '17:30',
            'status' => 'upcoming',
            'seats_available' => 24,
            'price_per_seat' => 45.00,
            'duration_minutes' => 90,
            'total_revenue' => 0.00,
            'booking_opens_at' => now(),
            'booking_closes_at' => now()->addDays(2)->setTimeFromTimeString('17:15'),
            'is_published' => true,
        ]);

        GameMatch::create([
            'branch_id' => $branches[4]->id,
            'home_team_id' => $psg->id,
            'away_team_id' => $bayern->id,
            'league' => 'Champions League',
            'match_date' => now()->addDays(4)->toDateString(),
            'kick_off' => '22:00',
            'status' => 'upcoming',
            'seats_available' => 24,
            'price_per_seat' => 35.00,
            'duration_minutes' => 90,
            'total_revenue' => 0.00,
            'booking_opens_at' => now(),
            'booking_closes_at' => now()->addDays(4)->setTimeFromTimeString('21:45'),
            'is_published' => true,
        ]);

        GameMatch::create([
            'branch_id' => $branches[5]->id,
            'home_team_id' => $juventus->id,
            'away_team_id' => $barcelona->id,
            'league' => 'Champions League',
            'match_date' => now()->addDays(6)->toDateString(),
            'kick_off' => '22:00',
            'status' => 'upcoming',
            'seats_available' => 24,
            'price_per_seat' => 40.00,
            'duration_minutes' => 90,
            'total_revenue' => 0.00,
            'booking_opens_at' => now(),
            'booking_closes_at' => now()->addDays(6)->setTimeFromTimeString('21:45'),
            'is_published' => true,
        ]);

        // Finished Matches (4)
        GameMatch::create([
            'branch_id' => $branches[0]->id,
            'home_team_id' => $arsenal->id,
            'away_team_id' => $chelsea->id,
            'league' => 'Premier League',
            'match_date' => now()->subDays(3)->toDateString(),
            'kick_off' => '20:00',
            'home_score' => 3,
            'away_score' => 1,
            'status' => 'finished',
            'seats_available' => 20,
            'price_per_seat' => 35.00,
            'duration_minutes' => 90,
            'total_revenue' => 140.00,
            'booking_opens_at' => now()->subDays(10),
            'booking_closes_at' => now()->subDays(3)->setTimeFromTimeString('19:45'),
            'is_published' => true,
        ]);

        GameMatch::create([
            'branch_id' => $branches[1]->id,
            'home_team_id' => $liverpool->id,
            'away_team_id' => $manCity->id,
            'league' => 'Premier League',
            'match_date' => now()->subDays(5)->toDateString(),
            'kick_off' => '17:30',
            'home_score' => 2,
            'away_score' => 2,
            'status' => 'finished',
            'seats_available' => 19,
            'price_per_seat' => 30.00,
            'duration_minutes' => 90,
            'total_revenue' => 150.00,
            'booking_opens_at' => now()->subDays(12),
            'booking_closes_at' => now()->subDays(5)->setTimeFromTimeString('17:15'),
            'is_published' => true,
        ]);

        GameMatch::create([
            'branch_id' => $branches[2]->id,
            'home_team_id' => $realMadrid->id,
            'away_team_id' => $psg->id,
            'league' => 'Champions League',
            'match_date' => now()->subDays(7)->toDateString(),
            'kick_off' => '22:00',
            'home_score' => 3,
            'away_score' => 2,
            'status' => 'finished',
            'seats_available' => 21,
            'price_per_seat' => 25.00,
            'duration_minutes' => 90,
            'total_revenue' => 75.00,
            'booking_opens_at' => now()->subDays(14),
            'booking_closes_at' => now()->subDays(7)->setTimeFromTimeString('21:45'),
            'is_published' => true,
        ]);

        GameMatch::create([
            'branch_id' => $branches[3]->id,
            'home_team_id' => $bayern->id,
            'away_team_id' => $juventus->id,
            'league' => 'Champions League',
            'match_date' => now()->subDays(10)->toDateString(),
            'kick_off' => '22:00',
            'home_score' => 1,
            'away_score' => 0,
            'status' => 'finished',
            'seats_available' => 23,
            'price_per_seat' => 30.00,
            'duration_minutes' => 90,
            'total_revenue' => 30.00,
            'booking_opens_at' => now()->subDays(17),
            'booking_closes_at' => now()->subDays(10)->setTimeFromTimeString('21:45'),
            'is_published' => true,
        ]);

        $this->command->info('Matches seeded successfully!');
        $this->command->info('2 live, 4 upcoming, 4 finished matches created.');
    }
}
