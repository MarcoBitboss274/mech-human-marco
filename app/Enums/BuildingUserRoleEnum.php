<?php

namespace App\Enums;

enum BuildingUserRoleEnum: string
{
    use BaseEnum;

    case ADMIN = 'admin';
    case MEMBER = 'member';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::MEMBER => 'Membro',
        };
    }
}
