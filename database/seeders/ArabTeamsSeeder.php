<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class ArabTeamsSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            // Saudi Pro League
            [
                'name' => 'Al Hilal',
                'short_name' => 'HIL',
                'logo' => 'teams/al-hilal.png',
                'league' => 'Saudi Pro League',
                'country' => 'Saudi Arabia',
                'is_popular' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Al Nassr',
                'short_name' => 'NAS',
                'logo' => 'teams/al-nassr.png',
                'league' => 'Saudi Pro League',
                'country' => 'Saudi Arabia',
                'is_popular' => true,
                'sort_order' => 21,
            ],
            [
                'name' => 'Al Ahli',
                'short_name' => 'AHL',
                'logo' => 'teams/al-ahli-saudi.png',
                'league' => 'Saudi Pro League',
                'country' => 'Saudi Arabia',
                'is_popular' => false,
                'sort_order' => 22,
            ],
            [
                'name' => 'Al Ittihad',
                'short_name' => 'ITT',
                'logo' => 'teams/al-ittihad.png',
                'league' => 'Saudi Pro League',
                'country' => 'Saudi Arabia',
                'is_popular' => false,
                'sort_order' => 23,
            ],
            [
                'name' => 'Al Shabab',
                'short_name' => 'SHB',
                'logo' => 'teams/al-shabab.png',
                'league' => 'Saudi Pro League',
                'country' => 'Saudi Arabia',
                'is_popular' => false,
                'sort_order' => 24,
            ],

            // Egyptian League
            [
                'name' => 'Al Ahly',
                'short_name' => 'AHY',
                'logo' => 'teams/al-ahly.png',
                'league' => 'Egyptian League',
                'country' => 'Egypt',
                'is_popular' => true,
                'sort_order' => 25,
            ],
            [
                'name' => 'Zamalek',
                'short_name' => 'ZAM',
                'logo' => 'teams/zamalek.png',
                'league' => 'Egyptian League',
                'country' => 'Egypt',
                'is_popular' => false,
                'sort_order' => 26,
            ],
        ];

        foreach ($teams as $team) {
            Team::firstOrCreate(
                ['name' => $team['name'], 'league' => $team['league']],
                $team
            );
        }
    }
}
