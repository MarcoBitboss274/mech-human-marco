<?php

namespace App\Enums;

enum RoleEnum: string
{
    use BaseEnum;

    case SUPERADMIN = 'superadmin';
    case ADMIN = 'admin';
    case AGENT = 'agent';
    case CUSTOMER = 'customer';
    case SUPPLIER = 'supplier';

    public function label(): string
    {
        return match ($this) {
            self::SUPERADMIN => 'Superadmin',
            self::ADMIN => 'Admin',
            self::AGENT => 'Agent',
            self::CUSTOMER => 'Customer',
            self::SUPPLIER => 'Supplier',
        };
    }
}