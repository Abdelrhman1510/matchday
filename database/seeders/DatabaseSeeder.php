<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting MatchDay database seeding...');
        $this->command->newLine();

        // Order matters! Seeders must run in dependency order
        $seeders = [
            RolesAndPermissionsSeeder::class,     // Must run first to create roles and permissions
            TeamSeeder::class,                    // No dependencies
            AchievementSeeder::class,             // No dependencies
            SubscriptionPlanSeeder::class,        // No dependencies
            UserSeeder::class,                    // No dependencies (creates fan profiles)
            PlatformAdminSeeder::class,           // Create platform admin user
            CafeSeeder::class,                    // Depends on: User (creates branches, hours, amenities)
            SeatingSeeder::class,                 // Depends on: Branch (creates sections and seats)
            MatchSeeder::class,                   // Depends on: Branch, Team
            BookingSeeder::class,                 // Depends on: User, Match, Branch, Seat
            LoyaltySeeder::class,                 // Depends on: User, Achievement, Booking
            OfferSeeder::class,                   // Depends on: Cafe
            ChatSeeder::class,                    // Depends on: Match, User
            PaymentSeeder::class,                 // Depends on: User, Booking
            NotificationSeeder::class,            // Depends on: User
        ];

        foreach ($seeders as $seeder) {
            $this->call($seeder);
            $this->command->newLine();
        }

        // Assign roles to existing seeded users
        $this->assignRolesToUsers();

        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->newLine();
        $this->displaySummary();
    }

    private function assignRolesToUsers(): void
    {
        $this->command->info('👤 Assigning roles to users...');

        // Ahmed = fan
        $ahmed = User::where('email', 'ahmed@matchday.app')->first();
        if ($ahmed) {
            $ahmed->assignRole('fan');
            $this->command->line('   ✓ Ahmed Hassan assigned to fan role');
        }

        // Omar = cafe_owner
        $omar = User::where('email', 'omar@matchday.app')->first();
        if ($omar) {
            $omar->assignRole('cafe_owner');
            $this->command->line('   ✓ Omar Al-Mansouri assigned to cafe_owner role');
        }

        // Assign cafe_owner role to other cafe owners
        $otherOwners = User::where('role', 'cafe_owner')
            ->whereNotIn('email', ['omar@matchday.app'])
            ->get();
        
        foreach ($otherOwners as $owner) {
            $owner->assignRole('cafe_owner');
        }
        $this->command->line('   ✓ Other cafe owners assigned to cafe_owner role');

        // Assign fan role to all other fans
        $otherFans = User::where('role', 'fan')
            ->whereNotIn('email', ['ahmed@matchday.app'])
            ->get();
        
        foreach ($otherFans as $fan) {
            $fan->assignRole('fan');
        }
        $this->command->line('   ✓ Other fans assigned to fan role');

        // Assign staff role to staff users if any exist
        $staffUsers = User::where('role', 'staff')->get();
        foreach ($staffUsers as $staff) {
            $staff->assignRole('staff');
        }
        if ($staffUsers->count() > 0) {
            $this->command->line('   ✓ Staff users assigned to staff role');
        }
    }

    private function displaySummary(): void
    {
        $this->command->info('📊 Seeding Summary:');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->line('� Roles & Permissions: 4 roles + 15 permissions');
        $this->command->line('👥 Users: 5 fans + 3 cafe owners + 5 staff');
        $this->command->line('🏆 Teams: 15 teams across 4 major leagues');
        $this->command->line('☕ Cafes: 3 cafes with 7 total branches');
        $this->command->line('💺 Seats: 24 seats per branch (168 total)');
        $this->command->line('⚽ Matches: 10 matches (2 live, 4 upcoming, 4 finished)');
        $this->command->line('📅 Bookings: 15 bookings with various statuses');
        $this->command->line('🎁 Offers: 5 promotional offers');
        $this->command->line('💳 Payments: Multiple payment methods and transactions');
        $this->command->line('💬 Chat: Chat rooms with messages for live matches');
        $this->command->line('🔔 Notifications: 10+ sample notifications');
        $this->command->line('🏅 Achievements: 7 achievements available');
        $this->command->line('⭐ Loyalty: Loyalty cards for all fans');
        $this->command->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->newLine();
        $this->command->info('🔑 Test Accounts:');
        $this->command->line('   Fan: ahmed@matchday.app / password (role: fan)');
        $this->command->line('   Owner: omar@matchday.app / password (role: cafe_owner)');
        $this->command->newLine();
    }
}
