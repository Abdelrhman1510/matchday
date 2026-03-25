<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Logos use ui-avatars.com — generates a consistent badge image per team
        // using real club colours. Replace with actual CDN URLs when available.
        $teams = [
            // Premier League
            [
                'name' => 'Manchester United',
                'short_name' => 'MUN',
                'logo' => 'https://ui-avatars.com/api/?name=MUN&background=CC0000&color=fff&size=200&bold=true&format=png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Liverpool',
                'short_name' => 'LIV',
                'logo' => 'https://ui-avatars.com/api/?name=LIV&background=C8102E&color=fff&size=200&bold=true&format=png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Chelsea',
                'short_name' => 'CHE',
                'logo' => 'https://ui-avatars.com/api/?name=CHE&background=034694&color=fff&size=200&bold=true&format=png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Arsenal',
                'short_name' => 'ARS',
                'logo' => 'https://ui-avatars.com/api/?name=ARS&background=EF0107&color=fff&size=200&bold=true&format=png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Manchester City',
                'short_name' => 'MCI',
                'logo' => 'https://ui-avatars.com/api/?name=MCI&background=6CABDD&color=fff&size=200&bold=true&format=png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Tottenham Hotspur',
                'short_name' => 'TOT',
                'logo' => 'https://ui-avatars.com/api/?name=TOT&background=132257&color=fff&size=200&bold=true&format=png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 6,
            ],
            // La Liga
            [
                'name' => 'Barcelona',
                'short_name' => 'BAR',
                'logo' => 'https://ui-avatars.com/api/?name=BAR&background=A50044&color=fff&size=200&bold=true&format=png',
                'league' => 'La Liga',
                'is_popular' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Real Madrid',
                'short_name' => 'RMA',
                'logo' => 'https://ui-avatars.com/api/?name=RMA&background=FEBE10&color=000&size=200&bold=true&format=png',
                'league' => 'La Liga',
                'is_popular' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Atletico Madrid',
                'short_name' => 'ATM',
                'logo' => 'https://ui-avatars.com/api/?name=ATM&background=CE2028&color=fff&size=200&bold=true&format=png',
                'league' => 'La Liga',
                'is_popular' => false,
                'sort_order' => 9,
            ],
            // Bundesliga
            [
                'name' => 'Bayern Munich',
                'short_name' => 'BAY',
                'logo' => 'https://ui-avatars.com/api/?name=BAY&background=DC052D&color=fff&size=200&bold=true&format=png',
                'league' => 'Bundesliga',
                'is_popular' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Borussia Dortmund',
                'short_name' => 'BVB',
                'logo' => 'https://ui-avatars.com/api/?name=BVB&background=FDE100&color=000&size=200&bold=true&format=png',
                'league' => 'Bundesliga',
                'is_popular' => false,
                'sort_order' => 11,
            ],
            // Serie A
            [
                'name' => 'Juventus',
                'short_name' => 'JUV',
                'logo' => 'https://ui-avatars.com/api/?name=JUV&background=000000&color=fff&size=200&bold=true&format=png',
                'league' => 'Serie A',
                'is_popular' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Inter Milan',
                'short_name' => 'INT',
                'logo' => 'https://ui-avatars.com/api/?name=INT&background=003DA5&color=fff&size=200&bold=true&format=png',
                'league' => 'Serie A',
                'is_popular' => false,
                'sort_order' => 13,
            ],
            [
                'name' => 'AC Milan',
                'short_name' => 'MIL',
                'logo' => 'https://ui-avatars.com/api/?name=MIL&background=CC0000&color=000&size=200&bold=true&format=png',
                'league' => 'Serie A',
                'is_popular' => false,
                'sort_order' => 14,
            ],
            // Ligue 1
            [
                'name' => 'Paris Saint-Germain',
                'short_name' => 'PSG',
                'logo' => 'https://ui-avatars.com/api/?name=PSG&background=004170&color=fff&size=200&bold=true&format=png',
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
