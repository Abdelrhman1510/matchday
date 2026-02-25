<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Define all permissions
        $permissionNames = [
            'manage-bookings',
            'view-bookings',
            'manage-matches',
            'manage-staff',
            'view-analytics',
            'manage-offers',
            'manage-menu',
            'manage-branches',
            'manage-seating',
            'manage-subscription',
            'scan-qr',
            'check-in-customers',
            'view-occupancy',
            'manage-cafe-profile',
            'full-admin-access',
            'manage-inventory',
            'process-payments',
        ];

        // Create all permissions idempotently and collect them
        $createdPermissions = [];
        foreach ($permissionNames as $permissionName) {
            $createdPermissions[$permissionName] = Permission::firstOrCreate(
                ['name' => $permissionName, 'guard_name' => 'web']
            );
        }

        // Create roles idempotently
        $fan = Role::firstOrCreate(['name' => 'fan', 'guard_name' => 'web']);
        $cafeOwner = Role::firstOrCreate(['name' => 'cafe_owner', 'guard_name' => 'web']);
        $staff = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        $platformAdmin = Role::firstOrCreate(['name' => 'platform_admin', 'guard_name' => 'web']);

        // Define cafe owner permissions (all except full-admin-access)
        $cafeOwnerPermissionNames = [
            'manage-bookings',
            'view-bookings',
            'manage-matches',
            'manage-staff',
            'view-analytics',
            'manage-offers',
            'manage-menu',
            'manage-branches',
            'manage-seating',
            'manage-subscription',
            'scan-qr',
            'check-in-customers',
            'view-occupancy',
            'manage-cafe-profile',
            'manage-inventory',
            'process-payments',
        ];

        // Sync permissions to cafe_owner (idempotent)
        $cafeOwnerPermissionIds = collect($cafeOwnerPermissionNames)
            ->map(fn ($name) => $createdPermissions[$name]->id)
            ->toArray();
        $cafeOwner->permissions()->syncWithoutDetaching($cafeOwnerPermissionIds);

        // Sync all permissions to platform_admin (idempotent)
        $allPermissionIds = collect($permissionNames)
            ->map(fn ($name) => $createdPermissions[$name]->id)
            ->toArray();
        $platformAdmin->permissions()->syncWithoutDetaching($allPermissionIds);

        // fan role has no special permissions assigned
        // staff role has no default permissions - assigned per-user when invited

        $this->command->info('✓ Roles and permissions created successfully!');
        $this->command->info('  - Roles: fan, cafe_owner, staff, platform_admin');
        $this->command->info('  - Permissions: ' . count($permissionNames) . ' permissions created');
        $this->command->info('  - cafe_owner: assigned ' . count($cafeOwnerPermissionNames) . ' permissions');
        $this->command->info('  - platform_admin: assigned all ' . count($permissionNames) . ' permissions');
    }
}
