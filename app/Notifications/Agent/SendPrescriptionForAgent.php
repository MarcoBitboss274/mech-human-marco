<?php

namespace App\Notifications\Agent;

use App\Models\Prescription;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendPrescriptionForAgent extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Prescription $prescription
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(config('mail.from.address'), NotificationService::SYSTEM_SENDER_NAME)
            ->subject('Mech & Human - Nuova richiesta ' . ($this->prescription?->typology ?? '-') . ' caricata da ' . ($this->prescription?->building?->name ?? '-'))
            ->markdown('mail.agent.send-prescription-for-agent', [
                'prescription' => $this->prescription,
                'operation' => $this->prescription?->operation,
                'notifiable' => $notifiable,
                'url' => route('operations.show', $this->prescription?->operation?->id ?? null)
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
