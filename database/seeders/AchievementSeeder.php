<?php

namespace Database\Seeders;

use App\Models\Achievement;
use Illuminate\Database\Seeder;

class AchievementSeeder extends Seeder
{
    public function run(): void
    {
        $achievements = [
            [
                'name' => 'First Match',
                'description' => 'Attended your first match viewing',
                'icon' => 'achievements/first-match.png',
                'criteria_type' => 'matches_attended',
                'criteria_value' => 1,
                'points_reward' => 10,
            ],
            [
                'name' => '10 Bookings',
                'description' => 'Made 10 successful bookings',
                'icon' => 'achievements/10-bookings.png',
                'criteria_type' => 'total_bookings',
                'criteria_value' => 10,
                'points_reward' => 50,
            ],
            [
                'name' => 'Gold Tier',
                'description' => 'Reached Gold loyalty tier',
                'icon' => 'achievements/gold-tier.png',
                'criteria_type' => 'loyalty_tier',
                'criteria_value' => 3,
                'points_reward' => 100,
            ],
            [
                'name' => 'VIP Member',
                'description' => 'Booked VIP seats 5 times',
                'icon' => 'achievements/vip-member.png',
                'criteria_type' => 'vip_bookings',
                'criteria_value' => 5,
                'points_reward' => 75,
            ],
            [
                'name' => '50 Matches',
                'description' => 'Epic! Attended 50+ matches',
                'icon' => 'achievements/50-matches.png',
                'criteria_type' => 'matches_attended',
                'criteria_value' => 50,
                'points_reward' => 200,
            ],
            [
                'name' => 'Legend',
                'description' => 'Reached Platinum tier and attended 100+ matches',
                'icon' => 'achievements/legend.png',
                'criteria_type' => 'matches_attended',
                'criteria_value' => 100,
                'points_reward' => 500,
            ],
            [
                'name' => 'Platinum Elite',
                'description' => 'Achieved Platinum loyalty tier',
                'icon' => 'achievements/platinum.png',
                'criteria_type' => 'loyalty_tier',
                'criteria_value' => 4,
                'points_reward' => 250,
            ],
        ];

        foreach ($achievements as $achievement) {
            Achievement::create($achievement);
        }

        $this->command->info('Achievements seeded successfully!');
    }
}
