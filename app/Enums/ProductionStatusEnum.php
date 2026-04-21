<?php

namespace App\Enums;

enum ProductionStatusEnum: string
{
    use BaseEnum;

    case CONFIRMED = 'confirmed';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::CONFIRMED => 'Confermato',
            self::CANCELED => 'Annullato',
        };
    }
}

