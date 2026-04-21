<?php

namespace App\Http\Controllers;

use App\Events\OperationChatMessageSent;
use App\Http\Requests\Chat\ListOperationChatMessagesRequest;
use App\Http\Requests\Chat\MarkOperationChatAsReadRequest;
use App\Http\Requests\Chat\SendOperationChatMessageRequest;
use App\Models\Operation;
use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Get one operation chat thread.
     */
    public function messages(ListOperationChatMessagesRequest $request, Operation $operation)
    {

        return response()->json([
            'messages' => ChatService::listMessages($operation, (int) ($request->integer('limit') ?: 50)),
        ]);
    }

    /**
     * Store one operation chat message.
     */
    public function store(SendOperationChatMessageRequest $request, Operation $operation)
    {
        $message = ChatService::sendMessage($operation, $request->user(), (string) $request->input('body'));

        broadcast(new OperationChatMessageSent($message))->toOthers();
        ChatService::notifyUnreadUpdatedRecipients($operation, $request->user());

        return response()->json([
            'message' => $message,
        ], 201);
    }

    /**
     * Mark operation chat messages as read for current user.
     */
    public function markAsRead(MarkOperationChatAsReadRequest $request, Operation $operation)
    {
        $read = ChatService::markAsRead($operation, $request->user());

        return response()->json([
            'read' => $read,
        ]);
    }

    /**
     * Get unread chat counters grouped by operation.
     */
    public function unreadByOperation(Request $request)
    {
        return response()->json([
            'items' => ChatService::unreadByOperation($request->user()),
        ]);
    }
}
