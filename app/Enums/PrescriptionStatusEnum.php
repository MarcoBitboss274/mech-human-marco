<?php

namespace App\Enums;

enum PrescriptionStatusEnum: string
{
    use BaseEnum;

    case DRAFT = 'draft';
    case SENT = 'sent';
    case IN_REVIEW = 'in_review';
    case REVISED = 'revised';
    case CONFIRMED = 'confirmed';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Bozza',
            self::SENT => 'Inviata',
            self::IN_REVIEW => 'In revisione',
            self::REVISED => 'Revisionata',
            self::CONFIRMED => 'Confermata',
        };
    }
}