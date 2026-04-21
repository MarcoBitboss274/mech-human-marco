<?php

namespace App\Policies;

use App\Models\Production;
use App\Models\User;

class ProductionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('productions.index');
    }

    public function view(User $user, Production $model): bool
    {
        return $user->can('productions.index');
    }

    public function create(User $user): bool
    {
        return $user->can('productions.create');
    }

    public function update(User $user, Production $model): bool
    {
        return $user->can('productions.edit');
    }

    public function delete(User $user, Production $model): bool
    {
        return $user->can('productions.destroy');
    }

    public function restore(User $user, Production $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, Production $model): bool
    {
        return false;
    }
}

