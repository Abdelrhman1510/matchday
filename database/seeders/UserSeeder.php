<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\FanProfile;
use App\Models\Team;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Main fan user
        $ahmed = User::create([
            'name' => 'Ahmed Hassan',
            'email' => 'ahmed@matchday.app',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '+966501234567',
            'role' => 'fan',
            'locale' => 'en',
            'is_active' => true,
        ]);

        // Create fan profile for Ahmed
        FanProfile::create([
            'user_id' => $ahmed->id,
            'favorite_team_id' => Team::where('name', 'Manchester United')->first()?->id,
            'matches_attended' => 0,
            'member_since' => now()->subYears(2),
        ]);

        // Main cafe owner
        $omar = User::create([
            'name' => 'Omar Al-Mansouri',
            'email' => 'omar@matchday.app',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '+966502345678',
            'role' => 'cafe_owner',
            'locale' => 'en',
            'is_active' => true,
        ]);

        // Additional cafe owners
        User::create([
            'name' => 'Khalid bin Faisal',
            'email' => 'khalid@matchday.app',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '+966503456789',
            'role' => 'cafe_owner',
            'locale' => 'ar',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Mohammed Al-Hashimi',
            'email' => 'mohammed@matchday.app',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'phone' => '+966504567890',
            'role' => 'cafe_owner',
            'locale' => 'ar',
            'is_active' => true,
        ]);

        // Additional fan users with profiles
        $fans = [
            [
                'name' => 'Sarah Al-Otaibi',
                'email' => 'sarah@example.com',
                'phone' => '+966505678901',
                'favorite_team' => 'Liverpool',
            ],
            [
                'name' => 'Abdullah Al-Zahrani',
                'email' => 'abdullah@example.com',
                'phone' => '+966506789012',
                'favorite_team' => 'Chelsea',
            ],
            [
                'name' => 'Fatima Al-Qurashi',
                'email' => 'fatima@example.com',
                'phone' => '+966507890123',
                'favorite_team' => 'Arsenal',
            ],
            [
                'name' => 'Yasser Al-Malki',
                'email' => 'yasser@example.com',
                'phone' => '+966508901234',
                'favorite_team' => 'Manchester City',
            ],
        ];

        foreach ($fans as $fanData) {
            $user = User::create([
                'name' => $fanData['name'],
                'email' => $fanData['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => $fanData['phone'],
                'role' => 'fan',
                'locale' => 'en',
                'is_active' => true,
            ]);

            FanProfile::create([
                'user_id' => $user->id,
                'favorite_team_id' => Team::where('name', $fanData['favorite_team'])->first()?->id,
                'matches_attended' => rand(0, 15),
                'member_since' => now()->subMonths(rand(1, 24)),
            ]);
        }

        // Staff users
        $staffUsers = [
            ['name' => 'Ali Al-Ghamdi', 'email' => 'ali@matchday.app', 'phone' => '+966509012345'],
            ['name' => 'Noor Al-Fadl', 'email' => 'noor@matchday.app', 'phone' => '+966500123456'],
            ['name' => 'Hassan Al-Tamimi', 'email' => 'hassan@matchday.app', 'phone' => '+966501234568'],
            ['name' => 'Maha Al-Rasheed', 'email' => 'maha@matchday.app', 'phone' => '+966502345679'],
            ['name' => 'Fahad Al-Sudairi', 'email' => 'fahad@matchday.app', 'phone' => '+966503456780'],
        ];

        foreach ($staffUsers as $staffData) {
            User::create([
                'name' => $staffData['name'],
                'email' => $staffData['email'],
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'phone' => $staffData['phone'],
                'role' => 'staff',
                'locale' => 'en',
                'is_active' => true,
            ]);
        }

        $this->command->info('Users seeded successfully!');
        $this->command->info('Main fan: ahmed@matchday.app / password');
        $this->command->info('Main owner: omar@matchday.app / password');
    }
}
