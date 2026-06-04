<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PlatformAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Idempotent: creates the platform admin only if missing, and always
     * ensures the platform_admin role is assigned. The password is a
     * pre-computed bcrypt hash (one-way) so no plaintext lives in the repo —
     * the 'hashed' cast on the User model stores it verbatim. Rotate by
     * replacing the hash below.
     */
    public function run(): void
    {
        // Ensure the role exists (guard_name must match the 'web' guard used by the dashboard).
        Role::firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@tab3s.com'],
            [
                'name'      => 'Platform Admin',
                // bcrypt hash of the platform admin password (set out-of-band).
                'password'  => '$2y$10$OV.ipGw.LIRad46mGQvpYOB3fvMkB.Ip6HqhwAGr3LE0zCyV6jqFy',
                'role'      => 'cafe_owner', // Base enum role; platform access comes from the Spatie role below.
                'is_active' => true,
            ]
        );

        if (!$admin->hasRole('platform_admin')) {
            $admin->assignRole('platform_admin');
        }

        $this->command->info('Platform admin ensured: admin@tab3s.com');
    }
}
