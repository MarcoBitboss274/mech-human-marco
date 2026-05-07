<?php

namespace App\Notifications\Supplier;

use App\Models\Supplier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class Invite extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Supplier $supplier,
        public string $url
    ) {
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
        return (new MailMessage)
            ->subject('Invito al team del fornitore')
            ->markdown('mail.supplier.invite', [
                'user' => $notifiable,
                'supplier' => $this->supplier,
                'url' => $this->url,
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
