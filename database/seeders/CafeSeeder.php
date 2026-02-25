<?php

namespace Database\Seeders;

use App\Models\Cafe;
use App\Models\Branch;
use App\Models\BranchHour;
use App\Models\BranchAmenity;
use App\Models\User;
use Illuminate\Database\Seeder;

class CafeSeeder extends Seeder
{
    public function run(): void
    {
        $cafeOwners = User::where('role', 'cafe_owner')->get();

        // Cafe 1: Champions Sports Lounge
        $cafe1 = Cafe::create([
            'owner_id' => $cafeOwners[0]->id,
            'name' => 'Champions Sports Lounge',
            'logo' => 'cafes/champions-logo.png',
            'description' => 'Premium sports viewing experience with state-of-the-art 4K screens and VIP seating. Perfect for enjoying live matches with friends.',
            'phone' => '+966112345678',
            'city' => 'Riyadh',
            'is_premium' => true,
            'avg_rating' => 4.8,
            'total_reviews' => 156,
            'subscription_plan' => 'elite',
        ]);

        // Champions - Branches
        $championsOlaya = Branch::create([
            'cafe_id' => $cafe1->id,
            'name' => 'Champions Olaya',
            'address' => 'Olaya Street, Al Olaya District, Riyadh',
            'latitude' => 24.6980,
            'longitude' => 46.6860,
            'total_seats' => 24,
            'is_open' => true,
        ]);

        $championsTahlia = Branch::create([
            'cafe_id' => $cafe1->id,
            'name' => 'Champions Tahlia',
            'address' => 'Tahlia Street, Al Andalus, Riyadh',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'total_seats' => 24,
            'is_open' => true,
        ]);

        $championsKhobar = Branch::create([
            'cafe_id' => $cafe1->id,
            'name' => 'Champions Khobar',
            'address' => 'Prince Turkey Street, Al Khobar',
            'latitude' => 26.2885,
            'longitude' => 50.2080,
            'total_seats' => 24,
            'is_open' => true,
        ]);

        // Cafe 2: Goal Zone Cafe
        $cafe2 = Cafe::create([
            'owner_id' => $cafeOwners[1]->id,
            'name' => 'Goal Zone Cafe',
            'logo' => 'cafes/goalzone-logo.png',
            'description' => 'Family-friendly sports cafe with a full menu and comfortable seating. Great atmosphere for all football fans.',
            'phone' => '+966112456789',
            'city' => 'Riyadh',
            'is_premium' => false,
            'avg_rating' => 4.5,
            'total_reviews' => 89,
            'subscription_plan' => 'pro',
        ]);

        $goalzoneExit5 = Branch::create([
            'cafe_id' => $cafe2->id,
            'name' => 'Goal Zone Exit 5',
            'address' => 'Exit 5, Northern Ring Road, Riyadh',
            'latitude' => 24.7744,
            'longitude' => 46.7386,
            'total_seats' => 24,
            'is_open' => true,
        ]);

        $goalzoneKing = Branch::create([
            'cafe_id' => $cafe2->id,
            'name' => 'Goal Zone King Fahd',
            'address' => 'King Fahd Road, Al Rabi, Riyadh',
            'latitude' => 24.6927,
            'longitude' => 46.7080,
            'total_seats' => 24,
            'is_open' => true,
        ]);

        // Cafe 3: Sky Sports Lounge
        $cafe3 = Cafe::create([
            'owner_id' => $cafeOwners[2]->id,
            'name' => 'Sky Sports Lounge',
            'logo' => 'cafes/skylounge-logo.png',
            'description' => 'Luxurious rooftop sports lounge with panoramic views and premium service. An unmatched viewing experience.',
            'phone' => '+966112567890',
            'city' => 'Jeddah',
            'is_premium' => true,
            'avg_rating' => 4.9,
            'total_reviews' => 203,
            'subscription_plan' => 'elite',
        ]);

        $skyJeddah = Branch::create([
            'cafe_id' => $cafe3->id,
            'name' => 'Sky Sports Jeddah',
            'address' => 'Tahlia Street, Al Andalus, Jeddah',
            'latitude' => 21.5433,
            'longitude' => 39.1728,
            'total_seats' => 24,
            'is_open' => true,
        ]);

        $skyRedSea = Branch::create([
            'cafe_id' => $cafe3->id,
            'name' => 'Sky Sports Red Sea',
            'address' => 'Corniche Road, Al Hamra, Jeddah',
            'latitude' => 21.5818,
            'longitude' => 39.1467,
            'total_seats' => 24,
            'is_open' => true,
        ]);

        // Add branch hours for all branches
        $branches = Branch::all();
        foreach ($branches as $branch) {
            $this->createBranchHours($branch);
            $this->createBranchAmenities($branch);
        }

        // Assign subscription plans to cafes
        $this->assignSubscriptions($cafe1, $cafe2, $cafe3);

        $this->command->info('Cafes, branches, hours, amenities, and subscriptions seeded successfully!');
    }

    private function assignSubscriptions($cafe1, $cafe2, $cafe3): void
    {
        $elite = \App\Models\SubscriptionPlan::where('slug', 'elite')->first();
        $pro = \App\Models\SubscriptionPlan::where('slug', 'pro')->first();

        // Champions Sports Lounge - Elite
        \App\Models\CafeSubscription::create([
            'cafe_id' => $cafe1->id,
            'plan_id' => $elite->id,
            'status' => 'active',
            'starts_at' => now()->subMonths(6),
            'expires_at' => now()->addMonths(6),
            'auto_renew' => true,
        ]);

        // Goal Zone Cafe - Pro
        \App\Models\CafeSubscription::create([
            'cafe_id' => $cafe2->id,
            'plan_id' => $pro->id,
            'status' => 'active',
            'starts_at' => now()->subMonths(3),
            'expires_at' => now()->addMonths(9),
            'auto_renew' => true,
        ]);

        // Sky Sports Lounge - Elite
        \App\Models\CafeSubscription::create([
            'cafe_id' => $cafe3->id,
            'plan_id' => $elite->id,
            'status' => 'active',
            'starts_at' => now()->subYear(),
            'expires_at' => now()->addYear(),
            'auto_renew' => true,
        ]);
    }

    private function createBranchHours(Branch $branch): void
    {
        // day_of_week: 0 = Sunday, 1 = Monday, 2 = Tuesday, ... 6 = Saturday
        $hours = [
            ['day_of_week' => 0, 'is_open' => true, 'open_time' => '12:00', 'close_time' => '02:00'], // Sunday
            ['day_of_week' => 1, 'is_open' => true, 'open_time' => '12:00', 'close_time' => '02:00'], // Monday
            ['day_of_week' => 2, 'is_open' => true, 'open_time' => '12:00', 'close_time' => '02:00'], // Tuesday
            ['day_of_week' => 3, 'is_open' => true, 'open_time' => '12:00', 'close_time' => '02:00'], // Wednesday
            ['day_of_week' => 4, 'is_open' => true, 'open_time' => '12:00', 'close_time' => '03:00'], // Thursday
            ['day_of_week' => 5, 'is_open' => true, 'open_time' => '14:00', 'close_time' => '03:00'], // Friday
            ['day_of_week' => 6, 'is_open' => true, 'open_time' => '12:00', 'close_time' => '03:00'], // Saturday
        ];

        foreach ($hours as $hour) {
            BranchHour::create(array_merge(['branch_id' => $branch->id], $hour));
        }
    }

    private function createBranchAmenities(Branch $branch): void
    {
        $amenities = [
            ['name' => 'Free WiFi', 'icon' => 'wifi'],
            ['name' => '4K Screens', 'icon' => 'tv'],
            ['name' => 'Full Menu', 'icon' => 'utensils'],
            ['name' => 'VIP Area', 'icon' => 'crown'],
            ['name' => 'Parking', 'icon' => 'parking'],
            ['name' => 'Air Conditioned', 'icon' => 'snowflake'],
        ];

        foreach ($amenities as $amenity) {
            BranchAmenity::create(array_merge(['branch_id' => $branch->id], $amenity));
        }
    }
}
