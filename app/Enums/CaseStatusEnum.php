<?php

namespace App\Enums;

enum CaseStatusEnum: string
{
    use BaseEnum;

    case OPEN = 'open';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Aperta',
            self::COMPLETED => 'Completata',
        };
    }
}
