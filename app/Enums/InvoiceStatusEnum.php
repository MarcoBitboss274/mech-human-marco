<?php

namespace App\Enums;

enum InvoiceStatusEnum: string
{
    use BaseEnum;

    case DRAFT = 'draft';
    case SENT = 'sent';
    case PAID = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Bozza',
            self::SENT => 'Inviata',
            self::PAID => 'Pagata',
        };
    }
}
