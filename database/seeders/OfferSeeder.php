<?php

namespace Database\Seeders;

use App\Models\Offer;
use App\Models\Cafe;
use Illuminate\Database\Seeder;

class OfferSeeder extends Seeder
{
    public function run(): void
    {
        $cafes = Cafe::all();

        // Offer 1: 30% Match Day Special
        Offer::create([
            'cafe_id' => $cafes[0]->id,
            'title' => '30% Match Day Special',
            'description' => 'Get 30% off on all bookings for Champions League matches. Valid for groups of 4 or more.',
            'image' => 'offers/match-day-special.jpg',
            'original_price' => 160.00,
            'offer_price' => 112.00,
            'discount_percent' => 30,
            'type' => 'percentage',
            'status' => 'active',
            'is_featured' => true,
            'valid_until' => now()->addMonths(2),
            'available_for' => 'all',
            'terms' => 'Valid only for Champions League matches. Minimum 4 guests required. Cannot be combined with other offers.',
            'usage_count' => 45,
        ]);

        // Offer 2: Buy 1 Get 1 Free
        Offer::create([
            'cafe_id' => $cafes[0]->id,
            'title' => 'Buy 1 Get 1 Free',
            'description' => 'Book 2 seats and pay for only 1! Perfect for bringing a friend to watch the match.',
            'image' => 'offers/buy-1-get-1.jpg',
            'original_price' => 90.00,
            'offer_price' => 45.00,
            'discount_percent' => 50,
            'type' => 'bogo',
            'status' => 'active',
            'is_featured' => true,
            'valid_until' => now()->addMonth(),
            'available_for' => 'all',
            'terms' => 'Valid for new customers only. Applies to standard seating only. Limited to one use per customer.',
            'usage_count' => 23,
        ]);

        // Offer 3: 20% Weekend Breakfast
        Offer::create([
            'cafe_id' => $cafes[1]->id,
            'title' => '20% Weekend Breakfast Deal',
            'description' => 'Early morning matches with 20% off breakfast menu items. Available on weekends only.',
            'image' => 'offers/breakfast-deal.jpg',
            'original_price' => 75.00,
            'offer_price' => 60.00,
            'discount_percent' => 20,
            'type' => 'percentage',
            'status' => 'active',
            'is_featured' => false,
            'valid_until' => now()->addWeeks(3),
            'available_for' => 'weekend',
            'terms' => 'Valid only on Friday and Saturday matches before 2 PM. Includes complimentary coffee.',
            'usage_count' => 12,
        ]);

        // Offer 4: Free Appetizer for Groups
        Offer::create([
            'cafe_id' => $cafes[1]->id,
            'title' => 'Free Appetizer with Group Booking',
            'description' => 'Book for 5+ guests and get a complimentary appetizer platter worth 50 SAR.',
            'image' => 'offers/group-appetizer.jpg',
            'original_price' => 50.00,
            'offer_price' => 0.00,
            'discount_percent' => 100,
            'type' => 'free_item',
            'status' => 'active',
            'is_featured' => false,
            'valid_until' => now()->addMonths(3),
            'available_for' => 'all',
            'terms' => 'Requires minimum of 5 confirmed guests. Appetizer selection by cafe. Must be claimed at time of booking.',
            'usage_count' => 34,
        ]);

        // Offer 5: 50% Champions League Night
        Offer::create([
            'cafe_id' => $cafes[2]->id,
            'title' => '50% Off Champions League Night',
            'description' => 'Massive discount on Premium seating for all Champions League knockout stage matches!',
            'image' => 'offers/ucl-50-off.jpg',
            'original_price' => 120.00,
            'offer_price' => 60.00,
            'discount_percent' => 50,
            'type' => 'percentage',
            'status' => 'active',
            'is_featured' => true,
            'valid_until' => now()->addMonths(1)->addWeeks(2),
            'available_for' => 'prime_time',
            'terms' => 'Valid for Silver tier loyalty members and above. Premium seats only. Subject to availability.',
            'usage_count' => 67,
        ]);

        $this->command->info('Offers seeded successfully!');
        $this->command->info('5 offers created across cafes.');
    }
}
