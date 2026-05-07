<?php

namespace App\Policies;

use App\Enums\RoleEnum;
use App\Models\Operation;
use App\Models\User;

class OperationSupplierChatPolicy
{
    public function viewMessages(User $user, Operation $operation): bool
    {
        return $this->canAccess($user, $operation);
    }

    public function sendMessage(User $user, Operation $operation): bool
    {
        return $this->canAccess($user, $operation);
    }

    public function receiveBroadcast(User $user, Operation $operation): bool
    {
        return $this->canAccess($user, $operation);
    }

    public function markAsRead(User $user, Operation $operation): bool
    {
        return $this->canAccess($user, $operation);
    }

    private function canAccess(User $user, Operation $operation): bool
    {
        if (in_array($user->role, [RoleEnum::ADMIN->value, RoleEnum::SUPERADMIN->value, RoleEnum::AGENT->value], true)) {
            return true;
        }

        if ($user->role !== RoleEnum::SUPPLIER->value) {
            return false;
        }

        $supplier = $user->suppliers()->first();
        if (! $supplier) {
            return false;
        }

        return $operation->suppliers()
            ->wherePivot('selected', true)
            ->where('suppliers.id', $supplier->id)
            ->exists();
    }
}
