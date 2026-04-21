<?php

namespace App\Enums;

enum QuoteStatusEnum: string
{
    use BaseEnum;

    case DRAFT = 'draft';
    case SENT = 'sent';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Bozza',
            self::SENT => 'Inviato',
            self::ACCEPTED => 'Accettato',
            self::REJECTED => 'Rifiutato',
            self::CANCELED => 'Annullato',
        };
    }
}