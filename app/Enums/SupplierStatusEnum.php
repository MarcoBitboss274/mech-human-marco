<?php

namespace App\Enums;

enum SupplierStatusEnum: string
{
    use BaseEnum;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Attivo',
            self::INACTIVE => 'Non attivo',
        };
    }
}
