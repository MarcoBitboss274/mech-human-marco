<?php

namespace App\Services;

use App\Enums\SupplierUserRoleEnum;
use App\Models\Supplier;
use App\Models\User;

class SupplierWorkspaceAuthorizationService
{
    /**
     * True if the given user is allowed to perform the workspace ability within their supplier team.
     * Uses the user's single supplier association (DB unique on supplier_user.user_id).
     */
    public function canForUser(User $user, string $ability): bool
    {
        if (! $user->isSupplier()) {
            return false;
        }

        $supplier = $this->resolveSupplier($user);
        if (! $supplier instanceof Supplier) {
            return false;
        }

        $role = $this->roleForUser($user, $supplier);
        if ($role === null) {
            return false;
        }

        return in_array($ability, SupplierWorkspacePermissionMap::abilitiesFor($role), true);
    }

    public function resolveSupplier(User $user): ?Supplier
    {
        return $user->suppliers()->first();
    }

    public function roleForUser(User $user, Supplier $supplier): ?SupplierUserRoleEnum
    {
        $row = $user->suppliers()->where('suppliers.id', $supplier->id)->first();
        $pivotRole = $row?->pivot?->role;
        if (! is_string($pivotRole) || $pivotRole === '') {
            return null;
        }

        return SupplierUserRoleEnum::tryFrom($pivotRole);
    }
}
