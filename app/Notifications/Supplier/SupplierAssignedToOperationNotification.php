<?php

namespace App\Notifications\Supplier;

use App\Models\Operation;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupplierAssignedToOperationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Operation $operation
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User) {
            return ['mail', 'database'];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = $notifiable instanceof User
            ? route('workspace.supplier.operations.show', ['operation' => $this->operation->id])
            : config('app.url');

        return (new MailMessage)
            ->from(config('mail.from.address'), NotificationService::LABORATORY_SENDER_NAME)
            ->subject('Nuova lavorazione assegnata')
            ->greeting('Ciao,')
            ->line('Ti è stata assegnata una nuova lavorazione di Mech & Human.')
            ->line('Codice di lotto: ' . ($this->operation->batch_number ?? '—'))
            ->action('Apri lavorazione', $url)
            ->line('Carica i documenti necessari a M&H per procedere con il preventivo.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'supplier.operation.assigned',
            'operation_id' => $this->operation->id,
            'batch_number' => $this->operation->batch_number,
        ];
    }
}
