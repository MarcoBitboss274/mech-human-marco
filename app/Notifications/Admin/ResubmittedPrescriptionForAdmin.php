<?php

namespace App\Notifications\Admin;

use App\Models\Prescription;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResubmittedPrescriptionForAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Prescription $prescription,
        public int $revisionNumber,
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
        $operation = $this->prescription?->operation;

        return (new MailMessage)
            ->from(config('mail.from.address'), NotificationService::SYSTEM_SENDER_NAME)
            ->subject('Prescrizione revisionata — ' . ($this->prescription->typology ?? '-'))
            ->markdown('mail.admin.resubmitted-prescription-for-admin', [
                'prescription' => $this->prescription,
                'operation' => $operation,
                'revisionNumber' => $this->revisionNumber,
                'url' => $operation !== null ? route('operations.show', $operation->id) : null,
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
