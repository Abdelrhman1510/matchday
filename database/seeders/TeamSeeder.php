<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        // Logos use TheSportsDB public badge CDN (real club crests).
        $teams = [
            // Premier League
            [
                'name' => 'Manchester United',
                'short_name' => 'MUN',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/xzqdr11517660252.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Liverpool',
                'short_name' => 'LIV',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/kfaher1737969724.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Chelsea',
                'short_name' => 'CHE',
                'logo' => 'https://www.thesportsdb.com/images/media/team/badge/pbf4ul1782638263.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Arsenal',
                'short_name' => 'ARS',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/uyhbfe1612467038.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'Manchester City',
                'short_name' => 'MCI',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/vwpvry1467462651.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'Tottenham Hotspur',
                'short_name' => 'TOT',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/dfyfhl1604094109.png',
                'league' => 'Premier League',
                'is_popular' => true,
                'sort_order' => 6,
            ],
            // La Liga
            [
                'name' => 'Barcelona',
                'short_name' => 'BAR',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/wq9sir1639406443.png',
                'league' => 'La Liga',
                'is_popular' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'Real Madrid',
                'short_name' => 'RMA',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/vwvwrw1473502969.png',
                'league' => 'La Liga',
                'is_popular' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'Atletico Madrid',
                'short_name' => 'ATM',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/0ulh3q1719984315.png',
                'league' => 'La Liga',
                'is_popular' => false,
                'sort_order' => 9,
            ],
            // Bundesliga
            [
                'name' => 'Bayern Munich',
                'short_name' => 'BAY',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/01ogkh1716960412.png',
                'league' => 'Bundesliga',
                'is_popular' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Borussia Dortmund',
                'short_name' => 'BVB',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/tqo8ge1716960353.png',
                'league' => 'Bundesliga',
                'is_popular' => false,
                'sort_order' => 11,
            ],
            // Serie A
            [
                'name' => 'Juventus',
                'short_name' => 'JUV',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/uxf0gr1742983727.png',
                'league' => 'Serie A',
                'is_popular' => true,
                'sort_order' => 12,
            ],
            [
                'name' => 'Inter Milan',
                'short_name' => 'INT',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/ryhu6d1617113103.png',
                'league' => 'Serie A',
                'is_popular' => false,
                'sort_order' => 13,
            ],
            [
                'name' => 'AC Milan',
                'short_name' => 'MIL',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/wvspur1448806617.png',
                'league' => 'Serie A',
                'is_popular' => false,
                'sort_order' => 14,
            ],
            // Ligue 1
            [
                'name' => 'Paris Saint-Germain',
                'short_name' => 'PSG',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/rwqrrq1473504808.png',
                'league' => 'Ligue 1',
                'is_popular' => true,
                'sort_order' => 15,
            ],
            // Saudi Pro League
            [
                'name' => 'Al Hilal',
                'short_name' => 'HIL',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/w0b80d1661656916.png',
                'league' => 'Saudi Pro League',
                'is_popular' => true,
                'sort_order' => 16,
            ],
            [
                'name' => 'Al Nassr',
                'short_name' => 'NAS',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/84yvqi1748524565.png',
                'league' => 'Saudi Pro League',
                'is_popular' => true,
                'sort_order' => 17,
            ],
            [
                'name' => 'Al Ittihad',
                'short_name' => 'ITT',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/8n1t1j1755192418.png',
                'league' => 'Saudi Pro League',
                'is_popular' => true,
                'sort_order' => 18,
            ],
            [
                'name' => 'Al Ahli',
                'short_name' => 'AHL',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/1bbtgb1755192301.png',
                'league' => 'Saudi Pro League',
                'is_popular' => true,
                'sort_order' => 19,
            ],
            [
                'name' => 'Al Ettifaq',
                'short_name' => 'ETT',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/m272h51694761970.png',
                'league' => 'Saudi Pro League',
                'is_popular' => false,
                'sort_order' => 20,
            ],
            [
                'name' => 'Al Taawoun',
                'short_name' => 'TAA',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/rlsmp91646835052.png',
                'league' => 'Saudi Pro League',
                'is_popular' => false,
                'sort_order' => 21,
            ],
            [
                'name' => 'Al Fateh',
                'short_name' => 'FAT',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/a5cjf41662659789.png',
                'league' => 'Saudi Pro League',
                'is_popular' => false,
                'sort_order' => 22,
            ],
            [
                'name' => 'Al Feiha',
                'short_name' => 'FAY',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/jl3spp1677530565.png',
                'league' => 'Saudi Pro League',
                'is_popular' => false,
                'sort_order' => 23,
            ],
            [
                'name' => 'Al Qadsiah',
                'short_name' => 'QAD',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/ok63wb1719134839.png',
                'league' => 'Saudi Pro League',
                'is_popular' => false,
                'sort_order' => 24,
            ],
            [
                'name' => 'Al Khaleej',
                'short_name' => 'KHA',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/mvf6ga1755192630.png',
                'league' => 'Saudi Pro League',
                'is_popular' => false,
                'sort_order' => 25,
            ],
            [
                'name' => 'Al Riyadh',
                'short_name' => 'RIY',
                'logo' => 'https://r2.thesportsdb.com/images/media/team/badge/i4o0zy1755193321.png',
                'league' => 'Saudi Pro League',
                'is_popular' => false,
                'sort_order' => 26,
            ],
        ];

        // Idempotent: match existing teams by name so re-running updates logos
        // and adds new teams WITHOUT duplicating rows or changing IDs (users'
        // favorite_team_id references these IDs). Safe to run on every deploy.
        foreach ($teams as $team) {
            Team::updateOrCreate(['name' => $team['name']], $team);
        }

        $this->command->info('Teams seeded successfully!');
    }
}
