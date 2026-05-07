<?php

namespace App\Events;

use App\Models\OperationSupplierChatMessage;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OperationSupplierChatMessageSent implements ShouldBroadcast
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OperationSupplierChatMessage $message
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('operations.supplier-chat.' . $this->message->operation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'operation.supplier-chat.message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'operation_id' => $this->message->operation_id,
                'body' => $this->message->body,
                'created_at' => $this->message->created_at?->toISOString(),
                'user' => [
                    'id' => $this->message->user?->id,
                    'name' => $this->message->user?->name,
                    'surname' => $this->message->user?->surname,
                ],
            ],
        ];
    }
}
