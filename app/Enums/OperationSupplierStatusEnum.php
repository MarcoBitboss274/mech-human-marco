<?php

namespace App\Enums;

enum OperationSupplierStatusEnum: string
{
    use BaseEnum;

    case TO_CONTACT = 'to_contact';
    case CONTACTED = 'contacted';

    public function label(): string
    {
        return match ($this) {
            self::TO_CONTACT => 'Da contattare',
            self::CONTACTED => 'Contattato',
        };
    }
}
