<?php

namespace App\Services;

use App\Enums\BuildingUserRoleEnum;
use App\Enums\WorkspaceAbilityEnum;
use App\Models\Building;
use App\Models\User;

class WorkspaceAuthorizationService
{
    public function canInBuilding(User $user, Building $building, string $ability): bool
    {
        if (! $user->isCustomer()) {
            return false;
        }

        if (! $user->canAccessBuilding($building)) {
            return false;
        }

        $role = $this->roleInBuilding($user, $building);
        if ($role === null) {
            return false;
        }

        if (! $this->passesUserCapabilityConstraints($user, $ability)) {
            return false;
        }

        return in_array($ability, WorkspacePermissionMap::abilitiesFor($role), true);
    }

    public function roleInBuilding(User $user, Building $building): ?BuildingUserRoleEnum
    {
        $row = $user
            ->buildings()
            ->where('buildings.id', $building->id)
            ->first();

        $pivotRole = $row?->pivot?->role;
        if (! is_string($pivotRole) || $pivotRole === '') {
            return null;
        }

        return BuildingUserRoleEnum::tryFrom($pivotRole);
    }

    private function passesUserCapabilityConstraints(User $user, string $ability): bool
    {
        if ($ability === WorkspaceAbilityEnum::PRESCRIPTIONS_SEND->value) {
            return (bool) $user->odontoiatra;
        }

        return true;
    }
}
