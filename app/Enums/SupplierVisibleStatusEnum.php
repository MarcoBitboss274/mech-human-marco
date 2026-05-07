<?php

namespace App\Enums;

enum SupplierVisibleStatusEnum: string
{
    use BaseEnum;

    case ASSIGNED_WAITING_DOCUMENTS = 'assigned_waiting_documents';
    case DOCUMENTS_SENT = 'documents_sent';
    case UNDER_EVALUATION = 'under_evaluation';
    case PRODUCTION_CONFIRMED = 'production_confirmed';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';

    public function label(): string
    {
        return match ($this) {
            self::ASSIGNED_WAITING_DOCUMENTS => 'Assegnata, in attesa documenti',
            self::DOCUMENTS_SENT => 'Documenti inviati, in valutazione M&H',
            self::UNDER_EVALUATION => 'In valutazione M&H',
            self::PRODUCTION_CONFIRMED => 'Produzione confermata',
            self::COMPLETED => 'Completata',
            self::CANCELED => 'Annullata',
        };
    }
}
