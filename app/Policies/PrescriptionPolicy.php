<?php

namespace App\Policies;

use App\Enums\PrescriptionStatusEnum;
use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('prescriptions.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Prescription $model): bool
    {
        return $user->can('prescriptions.index');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('prescriptions.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Prescription $model): bool
    {
        return $user->can('prescriptions.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Prescription $model): bool
    {
        if ($model->status !== PrescriptionStatusEnum::DRAFT->value) {
            return false;
        }

        return $user->can('prescriptions.destroy');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Prescription $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Prescription $model): bool
    {
        return false;
    }
}
