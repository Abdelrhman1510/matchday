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
                'name_ar' => 'المباراة الأولى',
                'description' => 'Attended your first match viewing',
                'description_ar' => 'حضرت أول مشاهدة لمباراة',
                'icon' => 'achievements/first-match.png',
                'criteria_type' => 'matches_attended',
                'criteria_value' => 1,
                'points_reward' => 10,
            ],
            [
                'name' => '10 Bookings',
                'name_ar' => '١٠ حجوزات',
                'description' => 'Made 10 successful bookings',
                'description_ar' => 'أتممت ١٠ حجوزات ناجحة',
                'icon' => 'achievements/10-bookings.png',
                'criteria_type' => 'total_bookings',
                'criteria_value' => 10,
                'points_reward' => 50,
            ],
            [
                'name' => 'Gold Tier',
                'name_ar' => 'المستوى الذهبي',
                'description' => 'Reached Gold loyalty tier',
                'description_ar' => 'وصلت إلى مستوى الولاء الذهبي',
                'icon' => 'achievements/gold-tier.png',
                'criteria_type' => 'loyalty_tier',
                'criteria_value' => 3,
                'points_reward' => 100,
            ],
            [
                'name' => 'VIP Member',
                'name_ar' => 'عضو كبار الشخصيات',
                'description' => 'Booked VIP seats 5 times',
                'description_ar' => 'حجزت مقاعد كبار الشخصيات ٥ مرات',
                'icon' => 'achievements/vip-member.png',
                'criteria_type' => 'vip_bookings',
                'criteria_value' => 5,
                'points_reward' => 75,
            ],
            [
                'name' => '50 Matches',
                'name_ar' => '٥٠ مباراة',
                'description' => 'Epic! Attended 50+ matches',
                'description_ar' => 'رائع! حضرت أكثر من ٥٠ مباراة',
                'icon' => 'achievements/50-matches.png',
                'criteria_type' => 'matches_attended',
                'criteria_value' => 50,
                'points_reward' => 200,
            ],
            [
                'name' => 'Legend',
                'name_ar' => 'أسطورة',
                'description' => 'Reached Platinum tier and attended 100+ matches',
                'description_ar' => 'وصلت إلى المستوى البلاتيني وحضرت أكثر من ١٠٠ مباراة',
                'icon' => 'achievements/legend.png',
                'criteria_type' => 'matches_attended',
                'criteria_value' => 100,
                'points_reward' => 500,
            ],
            [
                'name' => 'Platinum Elite',
                'name_ar' => 'نخبة البلاتين',
                'description' => 'Achieved Platinum loyalty tier',
                'description_ar' => 'حققت مستوى الولاء البلاتيني',
                'icon' => 'achievements/platinum.png',
                'criteria_type' => 'loyalty_tier',
                'criteria_value' => 4,
                'points_reward' => 250,
            ],
        ];

        // Idempotent: match by name so re-running fills in Arabic without dupes.
        foreach ($achievements as $achievement) {
            Achievement::updateOrCreate(['name' => $achievement['name']], $achievement);
        }

        $this->command->info('Achievements seeded successfully!');
    }
}
