<?php

namespace App\Notifications\Admin;

use App\Enums\BuildingUserRoleEnum;
use App\Models\Building;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBuildingForAdmin extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Building $building
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
        $owner = $this->building->users()->where('role', BuildingUserRoleEnum::ADMIN->value)->first();

        return (new MailMessage)
            ->from(config('mail.from.address'), NotificationService::SYSTEM_SENDER_NAME)
            ->subject('Richiesta validazione Nuova Struttura: ' . ($this->building->name ?? '-'))
            ->markdown('mail.admin.new-building-for-admin', [
                'building' => $this->building,
                'url' => route('buildings.show', $this->building->id),
                'owner' => $owner
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
