<?php

namespace App\Enums;

enum PrescriptionStatusEnum: string
{
    use BaseEnum;

    case DRAFT = 'draft';
    case SENT = 'sent';
    case CONFIRMED = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Bozza',
            self::SENT => 'Inviata',
            self::CONFIRMED => 'Confermata',
        };
    }
}