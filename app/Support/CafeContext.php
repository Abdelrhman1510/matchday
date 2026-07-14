<?php

namespace App\Support;

use App\Models\Cafe;

class CafeContext
{
    /**
     * @param int[] $accessibleBranchIds
     * @param string[] $permissions
     */
    public function __construct(
        public readonly Cafe $cafe,
        public readonly bool $isOwner,
        public readonly array $accessibleBranchIds,
        public readonly array $permissions,
    ) {}

    public function can(string $permission): bool
    {
        return $this->isOwner || in_array($permission, $this->permissions, true);
    }

    public function canAccessBranch(int $branchId): bool
    {
        return in_array($branchId, $this->accessibleBranchIds, true);
    }
}
