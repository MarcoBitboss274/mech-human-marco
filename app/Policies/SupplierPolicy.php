<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SupplierPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('suppliers.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Supplier $model): bool
    {
        return $user->can('suppliers.index');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('suppliers.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Supplier $model): bool
    {
        return $user->can('suppliers.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Supplier $model): bool
    {
        return $user->can('suppliers.destroy');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Supplier $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Supplier $model): bool
    {
        return false;
    }
}
