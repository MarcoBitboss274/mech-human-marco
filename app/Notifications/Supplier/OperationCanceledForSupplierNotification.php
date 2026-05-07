<?php

namespace App\Notifications\Supplier;

use App\Models\Operation;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OperationCanceledForSupplierNotification extends Notification implements ShouldQueue
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
            ->subject('Lavorazione annullata')
            ->greeting('Ciao,')
            ->line('La lavorazione ' . ($this->operation->batch_number ?? '—') . ' è stata annullata da Mech & Human.')
            ->action('Apri lavorazione', $url);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'supplier.operation.canceled',
            'operation_id' => $this->operation->id,
            'batch_number' => $this->operation->batch_number,
        ];
    }
}
