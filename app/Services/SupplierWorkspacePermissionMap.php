<?php

namespace App\Services;

use App\Enums\SupplierUserRoleEnum;
use App\Enums\WorkspaceAbilityEnum;

class SupplierWorkspacePermissionMap
{
    /**
     * Workspace abilities granted to a user based on their role within a supplier team.
     *
     * @return array<int, string>
     */
    public static function abilitiesFor(SupplierUserRoleEnum $role): array
    {
        return match ($role) {
            SupplierUserRoleEnum::ADMIN => [
                WorkspaceAbilityEnum::SUPPLIER_DASHBOARD_VIEW->value,
                WorkspaceAbilityEnum::SUPPLIER_PROFILE_VIEW->value,
                WorkspaceAbilityEnum::SUPPLIER_SETTINGS_VIEW->value,
                WorkspaceAbilityEnum::SUPPLIER_SETTINGS_UPDATE->value,
                WorkspaceAbilityEnum::SUPPLIER_TEAM_VIEW->value,
                WorkspaceAbilityEnum::SUPPLIER_TEAM_MANAGE->value,
                WorkspaceAbilityEnum::SUPPLIER_OPERATIONS_VIEW->value,
            ],
            SupplierUserRoleEnum::MEMBER => [
                WorkspaceAbilityEnum::SUPPLIER_DASHBOARD_VIEW->value,
                WorkspaceAbilityEnum::SUPPLIER_PROFILE_VIEW->value,
                WorkspaceAbilityEnum::SUPPLIER_OPERATIONS_VIEW->value,
            ],
        };
    }
}
