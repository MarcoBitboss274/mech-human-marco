<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Events\OperationSupplierChatUnreadUpdated;
use App\Models\Operation;
use App\Models\OperationSupplierChatMessage;
use App\Models\OperationSupplierChatRead;
use App\Models\User;
use App\Policies\OperationSupplierChatPolicy;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierChatService
{
    public static function listMessages(Operation $operation, int $limit = 50): Collection
    {
        return OperationSupplierChatMessage::query()
            ->where('operation_id', $operation->id)
            ->with('user:id,name,surname')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    public static function sendMessage(Operation $operation, User $user, string $body): OperationSupplierChatMessage
    {
        $message = OperationSupplierChatMessage::query()->create([
            'operation_id' => $operation->id,
            'user_id' => $user->id,
            'body' => trim($body),
        ]);

        return $message->load('user:id,name,surname');
    }

    public static function markAsRead(Operation $operation, User $user): OperationSupplierChatRead
    {
        $lastMessageId = OperationSupplierChatMessage::query()
            ->where('operation_id', $operation->id)
            ->max('id');

        return OperationSupplierChatRead::query()->updateOrCreate(
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

    public static function unreadByOperation(User $user): Collection
    {
        $rawCounters = DB::table('operation_supplier_chat_messages as messages')
            ->leftJoin('operation_supplier_chat_reads as reads', function ($join) use ($user) {
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

        $policy = app(OperationSupplierChatPolicy::class);

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

    public static function notifyUnreadUpdatedRecipients(Operation $operation, User $sender): void
    {
        $policy = app(OperationSupplierChatPolicy::class);

        $recipients = User::query()
            ->whereIn('role', [RoleEnum::ADMIN->value, RoleEnum::SUPERADMIN->value, RoleEnum::AGENT->value, RoleEnum::SUPPLIER->value])
            ->where('id', '!=', $sender->id)
            ->get()
            ->filter(fn (User $user) => $policy->receiveBroadcast($user, $operation));

        foreach ($recipients as $recipient) {
            broadcast(new OperationSupplierChatUnreadUpdated($recipient->id, $operation->id));
        }
    }
}
