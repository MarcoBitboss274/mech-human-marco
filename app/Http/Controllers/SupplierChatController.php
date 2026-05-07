<?php

namespace App\Http\Controllers;

use App\Events\OperationSupplierChatMessageSent;
use App\Http\Requests\SupplierChat\ListOperationSupplierChatMessagesRequest;
use App\Http\Requests\SupplierChat\MarkOperationSupplierChatAsReadRequest;
use App\Http\Requests\SupplierChat\SendOperationSupplierChatMessageRequest;
use App\Models\Operation;
use App\Services\SupplierChatService;
use Illuminate\Http\Request;

class SupplierChatController extends Controller
{
    public function messages(ListOperationSupplierChatMessagesRequest $request, Operation $operation)
    {
        return response()->json([
            'messages' => SupplierChatService::listMessages($operation, (int) ($request->integer('limit') ?: 50)),
        ]);
    }

    public function store(SendOperationSupplierChatMessageRequest $request, Operation $operation)
    {
        $message = SupplierChatService::sendMessage($operation, $request->user(), (string) $request->input('body'));

        broadcast(new OperationSupplierChatMessageSent($message))->toOthers();
        SupplierChatService::notifyUnreadUpdatedRecipients($operation, $request->user());

        return response()->json([
            'message' => $message,
        ], 201);
    }

    public function markAsRead(MarkOperationSupplierChatAsReadRequest $request, Operation $operation)
    {
        $read = SupplierChatService::markAsRead($operation, $request->user());

        return response()->json([
            'read' => $read,
        ]);
    }

    public function unreadByOperation(Request $request)
    {
        return response()->json([
            'items' => SupplierChatService::unreadByOperation($request->user()),
        ]);
    }
}
