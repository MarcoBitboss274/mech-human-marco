<?php

namespace App\Enums;

enum ProductionStatusEnum: string
{
    use BaseEnum;

    case CONFIRMED = 'confirmed';
    case CANCELED = 'canceled';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::CONFIRMED => 'Confermata',
            self::CANCELED => 'Annullata',
            self::COMPLETED => 'Completata',
        };
    }
}
