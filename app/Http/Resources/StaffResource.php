<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class StaffResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // This resource receives an array with 'staff_member' and 'permissions' keys
        $staffMember = is_array($this->resource) ? $this->resource['staff_member'] : $this->resource;
        $permissions = is_array($this->resource) && isset($this->resource['permissions']) 
            ? $this->resource['permissions'] 
            : [];

        // Count QR check-ins performed by this staff member
        $checkInsCount = 0;
        if ($staffMember->user_id) {
            $checkInsCount = DB::table('qr_scan_logs')
                ->where('scanned_by', $staffMember->user_id)
                ->where('result', 'success')
                ->count();
        }

        return [
            'id' => $staffMember->id,
            'staff_id_display' => '#STF-' . str_pad($staffMember->id, 4, '0', STR_PAD_LEFT),
            'cafe_id' => $staffMember->cafe_id,
            'role' => $staffMember->role,
            'invitation_status' => $staffMember->invitation_status,
            'invited_at' => $staffMember->created_at?->toISOString(),
            
            // User information
            'user' => [
                'id' => $staffMember->user->id ?? null,
                'name' => $staffMember->user->name ?? null,
                'email' => $staffMember->user->email ?? null,
                'avatar' => $staffMember->user->avatar ?? null,
            ],
            
            // Invited by
            'invited_by' => $this->when($staffMember->relationLoaded('invitedBy') && $staffMember->invitedBy, [
                'id' => $staffMember->invitedBy->id ?? null,
                'name' => $staffMember->invitedBy->name ?? null,
            ]),
            
            // Permissions
            'permissions' => $permissions,
            
            // Cafe details (when loaded)
            'cafe' => $this->when($staffMember->relationLoaded('cafe'), [
                'id' => $staffMember->cafe->id ?? null,
                'name' => $staffMember->cafe->name ?? null,
            ]),

            // Activity stats
            'check_ins_count' => $checkInsCount,
            'shifts_count' => 0, // Placeholder - shifts system is future scope
            
            // UI Helper Flags
            'status_badge' => $this->getStatusBadge($staffMember->invitation_status),
            'role_label' => $this->getRoleLabel($staffMember->role),
            'can_resend_invite' => $staffMember->invitation_status === 'pending',
            'permissions_count' => count($permissions),
        ];
    }

    /**
     * Get status badge text
     */
    private function getStatusBadge(string $status): string
    {
        return match($status) {
            'pending' => 'PENDING',
            'accepted' => 'ACTIVE',
            'rejected' => 'REJECTED',
            default => 'UNKNOWN',
        };
    }

    /**
     * Get role label
     */
    private function getRoleLabel(string $role): string
    {
        return match($role) {
            'admin' => 'Admin',
            'manager' => 'Manager',
            'staff' => 'Staff',
            default => ucfirst($role),
        };
    }
}
