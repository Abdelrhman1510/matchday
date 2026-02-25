<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CafeOwnerPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permission = Permission::firstOrCreate([
            'name' => 'manage-cafe-profile',
            'guard_name' => 'web',
        ]);

        $seatingPermission = Permission::firstOrCreate([
            'name' => 'manage-seating',
            'guard_name' => 'web',
        ]);

        $matchesPermission = Permission::firstOrCreate([
            'name' => 'manage-matches',
            'guard_name' => 'web',
        ]);

        Permission::firstOrCreate(['name' => 'view-bookings', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage-bookings', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'check-in-customers', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'scan-qr', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view-occupancy', 'guard_name' => 'web']);

        $this->command->info('✓ Permission "manage-cafe-profile" created/verified');
        $this->command->info('✓ Permission "manage-seating" created/verified');
        $this->command->info('✓ Permission "manage-matches" created/verified');
        $this->command->info('✓ Permission "view-bookings" created/verified');
        $this->command->info('✓ Permission "manage-bookings" created/verified');
        $this->command->info('✓ Permission "check-in-customers" created/verified');
        $this->command->info('✓ Permission "scan-qr" created/verified');
        $this->command->info('✓ Permission "view-occupancy" created/verified');

        // Create cafe-owner role
        $role = Role::firstOrCreate([
            'name' => 'cafe-owner',
            'guard_name' => 'web',
        ]);

        // Assign permissions to role
        if (!$role->hasPermissionTo('manage-cafe-profile')) {
            $role->givePermissionTo('manage-cafe-profile');
            $this->command->info('✓ Permission "manage-cafe-profile" assigned to "cafe-owner" role');
        } else {
            $this->command->info('✓ Permission "manage-cafe-profile" already assigned to "cafe-owner" role');
        }

        if (!$role->hasPermissionTo('manage-seating')) {
            $role->givePermissionTo('manage-seating');
            $this->command->info('✓ Permission "manage-seating" assigned to "cafe-owner" role');
        } else {
            $this->command->info('✓ Permission "manage-seating" already assigned to "cafe-owner" role');
        }

        if (!$role->hasPermissionTo('manage-matches')) {
            $role->givePermissionTo('manage-matches');
            $this->command->info('✓ Permission "manage-matches" assigned to "cafe-owner" role');
        } else {
            $this->command->info('✓ Permission "manage-matches" already assigned to "cafe-owner" role');
        }

        foreach (['view-bookings', 'manage-bookings', 'check-in-customers', 'scan-qr', 'view-occupancy'] as $perm) {
            if (!$role->hasPermissionTo($perm)) {
                $role->givePermissionTo($perm);
                $this->command->info("✓ Permission \"{$perm}\" assigned to \"cafe-owner\" role");
            } else {
                $this->command->info("✓ Permission \"{$perm}\" already assigned to \"cafe-owner\" role");
            }
        }

        $this->command->info('');
        $this->command->info('To assign the cafe-owner role to a user:');
        $this->command->info('  $user->assignRole(\'cafe-owner\');');
        $this->command->info('');
        $this->command->info('Or assign permissions directly:');
        $this->command->info('  $user->givePermissionTo(\'manage-cafe-profile\');');
        $this->command->info('  $user->givePermissionTo(\'manage-seating\');');
        $this->command->info('  $user->givePermissionTo(\'manage-matches\');');
        $this->command->info('  $user->givePermissionTo(\'view-bookings\');');
        $this->command->info('  $user->givePermissionTo(\'manage-bookings\');');
        $this->command->info('  $user->givePermissionTo(\'check-in-customers\');');
        $this->command->info('  $user->givePermissionTo(\'scan-qr\');');
        $this->command->info('  $user->givePermissionTo(\'view-occupancy\');');
    }
}
