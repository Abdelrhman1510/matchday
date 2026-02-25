<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $fans = User::where('role', 'fan')->get();

        foreach ($fans->take(3) as $fan) {
            // Booking confirmation notification
            Notification::create([
                'user_id' => $fan->id,
                'type' => 'booking_confirmed',
                'title' => 'Booking Confirmed!',
                'body' => 'Your booking has been confirmed. Show your QR code at the venue.',
                'data' => json_encode([
                    'booking_id' => rand(1, 10),
                    'action' => 'view_booking',
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(rand(1, 24)),
            ]);

            // Match reminder notification
            Notification::create([
                'user_id' => $fan->id,
                'type' => 'match_reminder',
                'title' => 'Match Starting Soon',
                'body' => 'Your match starts in 1 hour. Don\'t forget your booking!',
                'data' => json_encode([
                    'match_id' => rand(1, 10),
                    'action' => 'navigate_to_match',
                ]),
                'read_at' => now()->subMinutes(30),
                'created_at' => now()->subHours(rand(2, 48)),
            ]);

            // Loyalty points earned
            Notification::create([
                'user_id' => $fan->id,
                'type' => 'loyalty_points_earned',
                'title' => 'Points Earned! 🎉',
                'body' => 'You earned 50 loyalty points from your recent booking!',
                'data' => json_encode([
                    'points' => 50,
                    'action' => 'view_loyalty',
                ]),
                'read_at' => now()->subMinutes(rand(10, 120)),
                'created_at' => now()->subDays(rand(1, 5)),
            ]);

            // New offer notification
            Notification::create([
                'user_id' => $fan->id,
                'type' => 'offer_available',
                'title' => 'Special Offer Just for You!',
                'body' => '30% off on your next booking. Limited time only!',
                'data' => json_encode([
                    'offer_id' => rand(1, 5),
                    'action' => 'view_offer',
                ]),
                'read_at' => null,
                'created_at' => now()->subHours(rand(6, 72)),
            ]);
        }

        // Achievement unlocked for Ahmed
        $ahmed = $fans->first();
        Notification::create([
            'user_id' => $ahmed->id,
            'type' => 'achievement_unlocked',
            'title' => 'Achievement Unlocked! 🏆',
            'body' => 'Congratulations! You unlocked "Gold Tier" achievement.',
            'data' => json_encode([
                'achievement_id' => 3,
                'points_reward' => 100,
                'action' => 'view_achievements',
            ]),
            'read_at' => now()->subDays(2),
            'created_at' => now()->subDays(3),
        ]);

        // Match live notification
        Notification::create([
            'user_id' => $ahmed->id,
            'type' => 'match_live',
            'title' => 'Match is Live! ⚽',
            'body' => 'Manchester United vs Liverpool is now live. Join the chat!',
            'data' => json_encode([
                'match_id' => 1,
                'action' => 'join_chat',
            ]),
            'read_at' => now()->subMinutes(15),
            'created_at' => now()->subMinutes(45),
        ]);

        $this->command->info('Notifications seeded successfully!');
        $this->command->info('10+ notifications created for users.');
    }
}
