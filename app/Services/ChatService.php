<?php

namespace App\Services;

use App\Events\OperationChatUnreadUpdated;
use App\Models\Operation;
use App\Models\OperationChatMessage;
use App\Models\OperationChatRead;
use App\Models\User;
use App\Policies\OperationChatPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChatService
{
    /**
     * Get one operation chat thread.
     */
    public static function listMessages(Operation $operation, int $limit = 50): Collection
    {
        return OperationChatMessage::query()
            ->where('operation_id', $operation->id)
            ->with('user:id,name,surname')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Create and return one operation chat message.
     */
    public static function sendMessage(Operation $operation, User $user, string $body): OperationChatMessage
    {
        $message = OperationChatMessage::query()->create([
            'operation_id' => $operation->id,
            'user_id' => $user->id,
            'body' => trim($body),
        ]);

        return $message->load('user:id,name,surname');
    }

    /**
     * Mark all operation chat messages as read for one user.
     */
    public static function markAsRead(Operation $operation, User $user): OperationChatRead
    {
        $lastMessageId = OperationChatMessage::query()
            ->where('operation_id', $operation->id)
            ->max('id');

        return OperationChatRead::query()->updateOrCreate(
            [
                'operation_id' => $operation->id,
                'user_id' => $user->id,
            ],
            [
                'last_read_message_id' => $lastMessageId,
                'read_at' => now(),
            ],
        );
    }

    /**
     * Get unread chat counters grouped by operation for one user.
     */
    public static function unreadByOperation(User $user): Collection
    {
        $rawCounters = DB::table('operation_chat_messages as messages')
            ->leftJoin('operation_chat_reads as reads', function ($join) use ($user) {
                $join
                    ->on('reads.operation_id', '=', 'messages.operation_id')
                    ->where('reads.user_id', '=', $user->id);
            })
            ->join('operations', 'operations.id', '=', 'messages.operation_id')
            ->selectRaw('messages.operation_id')
            ->selectRaw('operations.batch_number')
            ->selectRaw('operations.typology')
            ->selectRaw('COUNT(messages.id) as unread_count')
            ->where('messages.user_id', '!=', $user->id)
            ->whereRaw('messages.id > COALESCE(reads.last_read_message_id, 0)')
            ->groupBy('messages.operation_id', 'operations.batch_number', 'operations.typology')
            ->orderByDesc('unread_count')
            ->get();

        if ($rawCounters->isEmpty()) {
            return collect();
        }

        $operationIds = $rawCounters->pluck('operation_id')->all();
        $operations = Operation::query()
            ->whereIn('id', $operationIds)
            ->get()
            ->keyBy('id');

        $policy = app(OperationChatPolicy::class);

        return $rawCounters
            ->filter(function (object $row) use ($operations, $policy, $user) {
                /** @var Operation|null $operation */
                $operation = $operations->get((int) $row->operation_id);

                return $operation !== null && $policy->viewMessages($user, $operation);
            })
            ->map(function (object $row) {
                return [
                    'operation_id' => (int) $row->operation_id,
                    'batch_number' => $row->batch_number,
                    'typology' => $row->typology,
                    'unread_count' => (int) $row->unread_count,
                ];
            })
            ->values();
    }

    /**
     * Broadcast unread refresh event to users allowed on this operation.
     */
    public static function notifyUnreadUpdatedRecipients(Operation $operation, User $sender): void
    {
        $policy = app(OperationChatPolicy::class);

        $recipients = User::query()
            ->where('id', '!=', $sender->id)
            ->get()
            ->filter(function (User $user) use ($operation, $policy) {
                return $policy->receiveBroadcast($user, $operation);
            });

        foreach ($recipients as $recipient) {
            broadcast(new OperationChatUnreadUpdated($recipient->id, $operation->id));
        }
    }
}

