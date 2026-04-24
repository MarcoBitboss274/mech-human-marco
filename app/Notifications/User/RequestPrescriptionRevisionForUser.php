<?php

namespace App\Notifications\User;

use App\Models\Prescription;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestPrescriptionRevisionForUser extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Prescription $prescription,
        public string $reason,
    ) {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $building = $this->prescription?->building;
        $operation = $this->prescription?->operation;

        return (new MailMessage)
            ->from(config('mail.from.address'), NotificationService::LABORATORY_SENDER_NAME)
            ->subject('Mech & Human - Richiesta di revisione prescrizione ' . ($this->prescription->typology ?? '-'))
            ->markdown('mail.user.request-prescription-revision-for-user', [
                'prescription' => $this->prescription,
                'operation' => $operation,
                'reason' => $this->reason,
                'url' => $building !== null && $operation !== null
                    ? route('workspace.operations.show', ['building' => $building->slug ?? $building->id, 'operation' => $operation->id])
                    : null,
                'notifiable' => $notifiable,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
