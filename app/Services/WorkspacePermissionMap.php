<?php

namespace App\Services;

use App\Enums\BuildingUserRoleEnum;
use App\Enums\WorkspaceAbilityEnum;

class WorkspacePermissionMap
{
    /**
     * @return array<int, string>
     */
    public static function abilitiesFor(BuildingUserRoleEnum $role): array
    {
        return match ($role) {
            BuildingUserRoleEnum::ADMIN => [
                WorkspaceAbilityEnum::BUILDING_VIEW->value,
                WorkspaceAbilityEnum::BUILDING_UPDATE->value,
                WorkspaceAbilityEnum::BUILDING_ADDRESSES_MANAGE->value,
                WorkspaceAbilityEnum::BUILDING_MEMBERS_INVITE->value,
                WorkspaceAbilityEnum::BUILDING_MEMBERS_EDIT->value,
                WorkspaceAbilityEnum::BUILDING_MEMBERS_REMOVE->value,
                WorkspaceAbilityEnum::OPERATIONS_VIEW->value,
                WorkspaceAbilityEnum::OPERATIONS_VIEW_ALL->value,
                WorkspaceAbilityEnum::OPERATIONS_CREATE->value,
                WorkspaceAbilityEnum::OPERATIONS_EDIT->value,
                WorkspaceAbilityEnum::PRESCRIPTIONS_VIEW->value,
                WorkspaceAbilityEnum::PRESCRIPTIONS_SEND->value,
                WorkspaceAbilityEnum::QUOTES_VIEW->value,
                WorkspaceAbilityEnum::QUOTES_ACCEPT->value,
                WorkspaceAbilityEnum::QUOTES_REJECT->value,
                WorkspaceAbilityEnum::ORDERS_VIEW->value,
                WorkspaceAbilityEnum::INVOICES_VIEW->value,
            ],
            BuildingUserRoleEnum::MEMBER => [
                WorkspaceAbilityEnum::BUILDING_VIEW->value,
                WorkspaceAbilityEnum::OPERATIONS_VIEW->value,
                WorkspaceAbilityEnum::OPERATIONS_CREATE->value,
                WorkspaceAbilityEnum::OPERATIONS_EDIT->value,
                WorkspaceAbilityEnum::PRESCRIPTIONS_VIEW->value,
                WorkspaceAbilityEnum::QUOTES_VIEW->value,
                WorkspaceAbilityEnum::QUOTES_ACCEPT->value,
                WorkspaceAbilityEnum::QUOTES_REJECT->value,
                WorkspaceAbilityEnum::ORDERS_VIEW->value,
                WorkspaceAbilityEnum::INVOICES_VIEW->value,
            ],
        };
    }
}
