<?php

namespace App\Notifications\Admin;

use App\Models\Operation;
use App\Models\Supplier;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplierProductionCompletedForAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Operation $operation,
        public Supplier $supplier,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(config('mail.from.address'), NotificationService::SYSTEM_SENDER_NAME)
            ->subject('Fornitore ha completato la produzione — lavorazione ' . ($this->operation->batch_number ?? '—'))
            ->greeting('Ciao,')
            ->line('Il fornitore "' . ($this->supplier->name ?? '—') . '" ha segnato come completata la produzione per la lavorazione ' . ($this->operation->batch_number ?? '—') . '.')
            ->action('Apri lavorazione', route('operations.show', $this->operation->id));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin.supplier.production_completed',
            'operation_id' => $this->operation->id,
            'supplier_id' => $this->supplier->id,
            'batch_number' => $this->operation->batch_number,
        ];
    }
}
