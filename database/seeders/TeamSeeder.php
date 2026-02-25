<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            // Premier League
            [
                'name' => 'Manchester United',
                'short_name' => 'MUN',
                'logo' => 'teams/man-united.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Liverpool',
                'short_name' => 'LIV',
                'logo' => 'teams/liverpool.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Chelsea',
                'short_name' => 'CHE',
                'logo' => 'teams/chelsea.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Arsenal',
                'short_name' => 'ARS',
                'logo' => 'teams/arsenal.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Manchester City',
                'short_name' => 'MCI',
                'logo' => 'teams/man-city.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Tottenham Hotspur',
                'short_name' => 'TOT',
                'logo' => 'teams/tottenham.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 6,
            ],
            // La Liga
            [
                'name' => 'Barcelona',
                'short_name' => 'BAR',
                'logo' => 'teams/barcelona.png',
                'league' => 'La Liga',
                'is_popular' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Real Madrid',
                'short_name' => 'RMA',
                'logo' => 'teams/real-madrid.png',
                'league' => 'La Liga',
                'is_popular' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Atletico Madrid',
                'short_name' => 'ATM',
                'logo' => 'teams/atletico.png',
                'league' => 'La Liga',
                'is_popular' => false,
                'sort_order' => 9,
            ],
            // Bundesliga
            [
                'name' => 'Bayern Munich',
                'short_name' => 'BAY',
                'logo' => 'teams/bayern.png',
                'league' => 'Bundesliga',
                'is_popular' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Borussia Dortmund',
                'short_name' => 'BVB',
                'logo' => 'teams/dortmund.png',
                'league' => 'Bundesliga',
                'is_popular' => false,
                'sort_order' => 11,
            ],
            // Serie A
            [
                'name' => 'Juventus',
                'short_name' => 'JUV',
                'logo' => 'teams/juventus.png',
                'league' => 'Serie A',
                'is_popular' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Inter Milan',
                'short_name' => 'INT',
                'logo' => 'teams/inter.png',
                'league' => 'Serie A',
                'is_popular' => false,
                'sort_order' => 13,
            ],
            [
                'name' => 'AC Milan',
                'short_name' => 'MIL',
                'logo' => 'teams/milan.png',
                'league' => 'Serie A',
                'is_popular' => false,
                'sort_order' => 14,
            ],
            // Ligue 1
            [
                'name' => 'Paris Saint-Germain',
                'short_name' => 'PSG',
                'logo' => 'teams/psg.png',
                'league' => 'Ligue 1',
                'is_popular' => true,
                'sort_order' => 15,
            ],
        ];

        foreach ($teams as $team) {
            Team::create($team);
        }

        $this->command->info('Teams seeded successfully!');
    }
}
