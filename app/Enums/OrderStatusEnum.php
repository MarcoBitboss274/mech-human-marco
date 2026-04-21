<?php

namespace App\Enums;

enum OrderStatusEnum: string
{
    use BaseEnum;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'In corso',
            self::CONFIRMED => 'Confermato',
        };
    }
}
