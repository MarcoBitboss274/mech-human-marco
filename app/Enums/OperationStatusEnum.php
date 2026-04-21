<?php

namespace App\Enums;

enum OperationStatusEnum: string
{
    use BaseEnum;

    case DRAFT = 'draft';
    case REQUESTED = 'requested';
    case IN_PROGRESS = 'in_progress';
    case WAITING_APPROVAL = 'waiting_approval';
    case PRODUCTION = 'production';
    case COMPLETED = 'completed';


    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Bozza',
            self::REQUESTED => 'Richiesta',
            self::IN_PROGRESS => 'In lavorazione',
            self::WAITING_APPROVAL => 'In approvazione',
            self::PRODUCTION => 'Produzione',
            self::COMPLETED => 'Completata',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::DRAFT => 10,
            self::REQUESTED => 20,
            self::IN_PROGRESS => 30,
            self::WAITING_APPROVAL => 40,
            self::PRODUCTION => 50,
            self::COMPLETED => 60,
        };
    }
}
