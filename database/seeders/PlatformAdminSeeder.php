<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PlatformAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create platform_admin role if it doesn't exist
        $role = Role::firstOrCreate(['name' => 'platform_admin']);

        // Create platform admin user  
        // Use 'cafe_owner' as base role, but assign platform_admin via Spatie
        $admin = User::firstOrCreate(
            ['email' => 'admin@matchday.app'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password'),
                'role' => 'cafe_owner', // Base role (enum constraint)
                'is_active' => true,
            ]
        );

        // Assign platform_admin role via Spatie
        if (!$admin->hasRole('platform_admin')) {
            $admin->assignRole('platform_admin');
        }

        $this->command->info('Platform admin created successfully!');
        $this->command->info('Email: admin@matchday.app');
        $this->command->info('Password: password');
    }
}
