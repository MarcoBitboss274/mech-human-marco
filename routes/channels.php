<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Operation;
use App\Policies\OperationChatPolicy;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('operations.chat.{operationId}', function ($user, int $operationId) {
    $operation = Operation::query()->find($operationId);

    if (! $operation) {
        return false;
    }

    return
        $user->canAccessOperation($operation) ||
        app(OperationChatPolicy::class)->receiveBroadcast($user, $operation);
});