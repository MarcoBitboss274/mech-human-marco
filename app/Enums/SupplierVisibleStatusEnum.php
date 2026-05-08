<?php

namespace App\Enums;

enum SupplierVisibleStatusEnum: string
{
    use BaseEnum;

    case NEW_CASE = 'new_case';
    case PRODUCTION_CONFIRMED = 'production_confirmed';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::NEW_CASE => 'Nuovo caso',
            self::PRODUCTION_CONFIRMED => 'Produzione confermata',
            self::COMPLETED => 'Completato',
        };
    }
}
