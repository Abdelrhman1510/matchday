<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use App\Support\CafeContext;

class CafeContextResolver
{
    public function resolve(User $user): ?CafeContext
    {
        // Owner takes precedence.
        $cafe = $user->ownedCafes()->first();
        $isOwner = $cafe !== null;

        if (!$cafe) {
            $membership = $user->staffMemberships()->accepted()->first();
            $cafe = $membership?->cafe;
        }

        if (!$cafe) {
            return null;
        }

        $cafeBranchIds = $cafe->branches()->pluck('id')->all();

        if ($isOwner) {
            return new CafeContext($cafe, true, $cafeBranchIds, []);
        }

        // Staff: assigned branches within this cafe.
        $branchIds = $user->branchAssignments()
            ->whereIn('branches.id', $cafeBranchIds)
            ->pluck('branches.id')
            ->all();

        // Effective permissions = Spatie ∪ custom branch_staff_permissions table.
        $spatie = $user->getAllPermissions()->pluck('name')->all();
        $custom = Permission::where('user_id', $user->id)->pluck('permission')->all();
        $permissions = array_values(array_unique(array_merge($spatie, $custom)));

        return new CafeContext($cafe, false, $branchIds, $permissions);
    }
}
