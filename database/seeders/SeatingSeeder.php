<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\SeatingSection;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatingSeeder extends Seeder
{
    public function run(): void
    {
        $branches = Branch::all();

        foreach ($branches as $branch) {
            // Main Screen Section - 6 seats
            $mainSection = SeatingSection::create([
                'branch_id' => $branch->id,
                'name' => 'Main Screen',
                'type' => 'main_screen',
                'total_seats' => 6,
                'extra_cost' => 0.00,
                'icon' => 'tv',
            ]);

            for ($i = 1; $i <= 6; $i++) {
                Seat::create([
                    'section_id' => $mainSection->id,
                    'label' => "M{$i}",
                    'is_available' => true,
                ]);
            }

            // VIP Section - 4 seats (+50 SAR)
            $vipSection = SeatingSection::create([
                'branch_id' => $branch->id,
                'name' => 'VIP Area',
                'type' => 'vip',
                'total_seats' => 4,
                'extra_cost' => 50.00,
                'icon' => 'crown',
            ]);

            for ($i = 1; $i <= 4; $i++) {
                Seat::create([
                    'section_id' => $vipSection->id,
                    'label' => "V{$i}",
                    'is_available' => true,
                ]);
            }

            // Premium Section - 6 seats (+30 SAR)
            $premiumSection = SeatingSection::create([
                'branch_id' => $branch->id,
                'name' => 'Premium',
                'type' => 'premium',
                'total_seats' => 6,
                'extra_cost' => 30.00,
                'icon' => 'star',
            ]);

            for ($i = 1; $i <= 6; $i++) {
                Seat::create([
                    'section_id' => $premiumSection->id,
                    'label' => "P{$i}",
                    'is_available' => true,
                ]);
            }

            // Standard Section - 8 seats (no extra cost)
            $standardSection = SeatingSection::create([
                'branch_id' => $branch->id,
                'name' => 'Standard',
                'type' => 'standard',
                'total_seats' => 8,
                'extra_cost' => 0.00,
                'icon' => 'chair',
            ]);

            for ($i = 1; $i <= 8; $i++) {
                Seat::create([
                    'section_id' => $standardSection->id,
                    'label' => "S{$i}",
                    'is_available' => true,
                ]);
            }
        }

        $this->command->info('Seating sections and seats seeded successfully!');
        $this->command->info('Each branch has 24 seats: 6 Main + 4 VIP + 6 Premium + 8 Standard');
    }
}
