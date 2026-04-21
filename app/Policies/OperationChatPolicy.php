<?php

namespace App\Policies;

use App\Models\Operation;
use App\Models\User;

class OperationChatPolicy
{
    /**
     * Determine whether the user can view operation chat messages.
     */
    public function viewMessages(User $user, Operation $operation): bool
    {
        return $this->canAccessOperationChat($user, $operation) && $user->can('chat.read');
    }

    /**
     * Determine whether the user can send operation chat messages.
     */
    public function sendMessage(User $user, Operation $operation): bool
    {
        return $this->viewMessages($user, $operation) && $user->can('chat.send');
    }

    /**
     * Determine whether the user can receive broadcast events for operation chat.
     */
    public function receiveBroadcast(User $user, Operation $operation): bool
    {
        return $this->viewMessages($user, $operation);
    }

    /**
     * Determine whether the user can mark operation chat messages as read.
     */
    public function markAsRead(User $user, Operation $operation): bool
    {
        return $this->viewMessages($user, $operation);
    }

    /**
     * Shared authorization check for operation and chat access.
     */
    private function canAccessOperationChat(User $user, Operation $operation): bool
    {
        return app(OperationPolicy::class)->view($user, $operation);
    }
}

