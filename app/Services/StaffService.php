<?php

namespace App\Services;

use App\Models\Cafe;
use App\Models\StaffMember;
use App\Models\User;
use App\Notifications\StaffInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class StaffService
{
    /**
     * Default permissions by role
     */
    private const ROLE_PERMISSIONS = [
        'admin' => [
            'manage-bookings',
            'view-bookings',
            'view-analytics',
            'manage-matches',
            'scan-qr',
            'check-in-customers',
            'view-occupancy',
            'manage-staff',
            'manage-seating',
            'manage-branches',
            'manage-offers',
            'manage-menu',
        ],
        'manager' => [
            'manage-bookings',
            'view-bookings',
            'view-analytics',
            'manage-matches',
            'scan-qr',
            'check-in-customers',
            'view-occupancy',
        ],
        'staff' => [
            'view-bookings',
            'scan-qr',
            'check-in-customers',
        ],
    ];

    /**
     * Get all staff for a cafe with permissions
     */
    public function listStaff(Cafe $cafe): array
    {
        $staff = $cafe->staffMembers()
            ->with([
                'user:id,name,email,avatar,role',
                'invitedBy:id,name',
            ])
            ->orderBy('invitation_status')
            ->orderBy('created_at', 'desc')
            ->get();

        // Add permissions for each staff member
        $staffWithPermissions = $staff->map(function ($staffMember) {
            $user = $staffMember->user;
            $permissions = $user ? $user->getAllPermissions()->pluck('name')->toArray() : [];

            return [
                'staff_member' => $staffMember,
                'permissions' => $permissions,
            ];
        });

        return $staffWithPermissions->toArray();
    }

    /**
     * Invite a new staff member
     */
    public function inviteStaff(Cafe $cafe, User $invitedBy, array $data): StaffMember
    {
        return DB::transaction(function () use ($cafe, $invitedBy, $data) {
            // Check if user exists by email
            $user = User::where('email', $data['email'])->first();

            // If user doesn't exist, create them
            if (!$user) {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make(Str::random(32)), // Random password, will reset on first login
                    'role' => 'staff',
                    'is_active' => true,
                ]);
            }

            // Check if they're already staff at this cafe
            $existingStaff = StaffMember::where('cafe_id', $cafe->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingStaff) {
                throw new \Exception('This user is already a staff member at this cafe');
            }

            // Create staff member record
            $staffMember = StaffMember::create([
                'cafe_id' => $cafe->id,
                'user_id' => $user->id,
                'role' => $data['role'],
                'invited_by' => $invitedBy->id,
                'invitation_status' => 'pending',
            ]);

            // Assign permissions
            $permissions = $data['permissions'] ?? $this->getDefaultPermissionsByRole($data['role']);
            $this->syncPermissions($user, $permissions);

            // Generate signed URL for invitation
            $signedUrl = URL::temporarySignedRoute(
                'auth.staff.accept-invite',
                now()->addDays(7),
                ['token' => $this->generateInvitationToken($staffMember)]
            );

            // Send invitation notification
            $user->notify(new StaffInvitationNotification($cafe, $data['role'], $signedUrl));

            return $staffMember->load(['user', 'invitedBy']);
        });
    }

    /**
     * Get staff member details with permissions
     */
    public function getStaffDetail(StaffMember $staffMember): array
    {
        $staffMember->load(['user', 'invitedBy', 'cafe']);
        $permissions = $staffMember->user->getAllPermissions()->pluck('name')->toArray();

        return [
            'staff_member' => $staffMember,
            'permissions' => $permissions,
        ];
    }

    /**
     * Update staff role and/or permissions
     */
    public function updateStaff(StaffMember $staffMember, array $data): StaffMember
    {
        return DB::transaction(function () use ($staffMember, $data) {
            // Update role if provided
            if (isset($data['role'])) {
                $staffMember->update(['role' => $data['role']]);
            }

            // Update permissions if provided
            if (isset($data['permissions'])) {
                $this->syncPermissions($staffMember->user, $data['permissions']);
            } elseif (isset($data['role'])) {
                // If only role updated, apply default permissions for that role
                $defaultPermissions = $this->getDefaultPermissionsByRole($data['role']);
                $this->syncPermissions($staffMember->user, $defaultPermissions);
            }

            return $staffMember->fresh(['user', 'invitedBy']);
        });
    }

    /**
     * Remove staff member and revoke all permissions
     */
    public function removeStaff(StaffMember $staffMember): bool
    {
        return DB::transaction(function () use ($staffMember) {
            $user = $staffMember->user;

            // Revoke all cafe-related permissions
            $cafePermissions = [
                'manage-bookings',
                'view-bookings',
                'manage-matches',
                'view-analytics',
                'manage-offers',
                'manage-menu',
                'manage-branches',
                'manage-seating',
                'scan-qr',
                'check-in-customers',
                'view-occupancy',
                'manage-staff',
            ];

            foreach ($cafePermissions as $permission) {
                if ($user->hasPermissionTo($permission)) {
                    $user->revokePermissionTo($permission);
                }
            }

            // Soft delete the staff member record
            return $staffMember->delete();
        });
    }

    /**
     * Resend invitation email
     */
    public function resendInvite(StaffMember $staffMember): void
    {
        if ($staffMember->invitation_status === 'accepted') {
            throw new \Exception('This invitation has already been accepted');
        }

        // Generate new signed URL
        $signedUrl = URL::temporarySignedRoute(
            'auth.staff.accept-invite',
            now()->addDays(7),
            ['token' => $this->generateInvitationToken($staffMember)]
        );

        // Send invitation notification
        $staffMember->user->notify(
            new StaffInvitationNotification(
                $staffMember->cafe,
                $staffMember->role,
                $signedUrl
            )
        );
    }

    /**
     * Accept staff invitation
     */
    public function acceptInvitation(string $token): StaffMember
    {
        return DB::transaction(function () use ($token) {
            // Decode token to get staff member ID
            $staffMemberId = $this->decodeInvitationToken($token);

            $staffMember = StaffMember::findOrFail($staffMemberId);

            if ($staffMember->invitation_status === 'accepted') {
                throw new \Exception('This invitation has already been accepted');
            }

            // Update invitation status
            $staffMember->update(['invitation_status' => 'accepted']);

            return $staffMember->fresh(['user', 'cafe']);
        });
    }

    /**
     * Get all available roles and their default permissions
     */
    public function getRolesAndPermissions(): array
    {
        return [
            'roles' => [
                [
                    'value' => 'admin',
                    'label' => 'Admin',
                    'description' => 'Full access to all cafe management features',
                    'default_permissions' => self::ROLE_PERMISSIONS['admin'],
                ],
                [
                    'value' => 'manager',
                    'label' => 'Manager',
                    'description' => 'Manage bookings, matches, and day-to-day operations',
                    'default_permissions' => self::ROLE_PERMISSIONS['manager'],
                ],
                [
                    'value' => 'staff',
                    'label' => 'Staff',
                    'description' => 'Basic access for checking in customers and viewing bookings',
                    'default_permissions' => self::ROLE_PERMISSIONS['staff'],
                ],
            ],
            'available_permissions' => $this->getAvailablePermissions(),
        ];
    }

    /**
     * Get all available permissions
     */
    private function getAvailablePermissions(): array
    {
        $permissions = [
            'manage-bookings' => 'Manage Bookings',
            'view-bookings' => 'View Bookings',
            'manage-matches' => 'Manage Matches',
            'view-analytics' => 'View Analytics',
            'manage-offers' => 'Manage Offers',
            'manage-menu' => 'Manage Menu',
            'manage-branches' => 'Manage Branches',
            'manage-seating' => 'Manage Seating',
            'scan-qr' => 'Scan QR Codes',
            'check-in-customers' => 'Check-in Customers',
            'view-occupancy' => 'View Occupancy',
            'manage-staff' => 'Manage Staff',
        ];

        return collect($permissions)->map(fn($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->toArray();
    }

    /**
     * Get default permissions by role
     */
    private function getDefaultPermissionsByRole(string $role): array
    {
        return self::ROLE_PERMISSIONS[$role] ?? [];
    }

    /**
     * Sync permissions for a user
     */
    private function syncPermissions(User $user, array $permissions): void
    {
        // Get all cafe-related permissions that the user currently has
        $cafePermissions = [
            'manage-bookings',
            'view-bookings',
            'manage-matches',
            'view-analytics',
            'manage-offers',
            'manage-menu',
            'manage-branches',
            'manage-seating',
            'scan-qr',
            'check-in-customers',
            'view-occupancy',
            'manage-staff',
        ];

        // Revoke all cafe permissions first
        foreach ($cafePermissions as $permission) {
            if ($user->hasPermissionTo($permission)) {
                $user->revokePermissionTo($permission);
            }
        }

        // Grant new permissions
        foreach ($permissions as $permission) {
            if (in_array($permission, $cafePermissions)) {
                $user->givePermissionTo($permission);
            }
        }
    }

    /**
     * Generate invitation token
     */
    private function generateInvitationToken(StaffMember $staffMember): string
    {
        return base64_encode(json_encode([
            'staff_member_id' => $staffMember->id,
            'cafe_id' => $staffMember->cafe_id,
        ]));
    }

    /**
     * Decode invitation token
     */
    private function decodeInvitationToken(string $token): int
    {
        $decoded = json_decode(base64_decode($token), true);
        
        if (!$decoded || !isset($decoded['staff_member_id'])) {
            throw new \Exception('Invalid invitation token');
        }

        return $decoded['staff_member_id'];
    }
}
