<?php

namespace Database\Seeders;

use App\Models\LoyaltyCard;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use App\Models\Achievement;
use App\Models\Booking;
use Illuminate\Database\Seeder;

class LoyaltySeeder extends Seeder
{
    public function run(): void
    {
        $fans = User::where('role', 'fan')->get();
        $achievements = Achievement::all();

        // Ahmed Hassan - Gold tier with 340 points
        $ahmed = $fans[0];
        $ahmedCard = LoyaltyCard::create([
            'user_id' => $ahmed->id,
            'card_number' => 'MD' . date('Y') . str_pad(1, 7, '0', STR_PAD_LEFT),
            'points' => 340,
            'tier' => 'gold',
            'total_points_earned' => 540,
            'issued_date' => now()->subYears(2),
        ]);

        // Add transactions for Ahmed
        LoyaltyTransaction::create([
            'loyalty_card_id' => $ahmedCard->id,
            'booking_id' => Booking::where('user_id', $ahmed->id)->first()?->id,
            'points' => 50,
            'type' => 'earned',
            'description' => 'Earned from booking confirmation',
        ]);

        LoyaltyTransaction::create([
            'loyalty_card_id' => $ahmedCard->id,
            'points' => 100,
            'type' => 'earned',
            'description' => 'Achievement unlocked: Gold Tier',
        ]);

        LoyaltyTransaction::create([
            'loyalty_card_id' => $ahmedCard->id,
            'points' => 200,
            'type' => 'redeemed',
            'description' => 'Redeemed for VIP seat upgrade',
        ]);

        LoyaltyTransaction::create([
            'loyalty_card_id' => $ahmedCard->id,
            'points' => 150,
            'type' => 'earned',
            'description' => 'Bonus points - frequent visitor',
        ]);

        // Ahmed's achievements
        $ahmed->achievements()->attach([
            $achievements->where('name', 'First Match')->first()->id => ['unlocked_at' => now()->subMonths(6)],
            $achievements->where('name', '10 Bookings')->first()->id => ['unlocked_at' => now()->subMonths(3)],
            $achievements->where('name', 'Gold Tier')->first()->id => ['unlocked_at' => now()->subMonth()],
        ]);

        // Other fans with loyalty cards
        foreach ($fans->skip(1) as $index => $fan) {
            $points = [50, 120, 200, 85][min($index, 3)];
            $tier = $points >= 200 ? 'silver' : 'bronze';
            
            $card = LoyaltyCard::create([
                'user_id' => $fan->id,
                'card_number' => 'MD' . date('Y') . str_pad($index + 2, 7, '0', STR_PAD_LEFT),
                'points' => $points,
                'tier' => $tier,
                'total_points_earned' => $points + rand(20, 100),
                'issued_date' => now()->subMonths(rand(1, 12)),
            ]);

            // Add a transaction
            LoyaltyTransaction::create([
                'loyalty_card_id' => $card->id,
                'points' => rand(20, 50),
                'type' => 'earned',
                'description' => 'Earned from booking confirmation',
            ]);

            // Give first match achievement to all
            $fan->achievements()->attach([
                $achievements->where('name', 'First Match')->first()->id => ['unlocked_at' => now()->subMonths(rand(1, 12))],
            ]);
        }

        $this->command->info('Loyalty cards, transactions, and achievements seeded successfully!');
        $this->command->info('Ahmed Hassan: 340 points, Gold tier');
    }
}
