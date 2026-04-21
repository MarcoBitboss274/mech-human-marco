<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\Operation;
use App\Models\User;

class OperationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('operations.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Operation $model): bool
    {
        if (! $user->can('operations.index')) {
            return false;
        }

        if ($user->can('operations.view-all')) {
            return true;
        }

        return Building::query()
            ->where('id', $model->building_id)
            ->where('agent_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('operations.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Operation $model): bool
    {
        return $user->can('operations.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Operation $model): bool
    {
        return $user->can('operations.destroy');
    }

    /**
     * Determine whether the user can manage operation suppliers.
     */
    public function manageSupplier(User $user, Operation $model): bool
    {
        return $user->can('operations.supplier.manage');
    }

    /**
     * Determine whether the user can manage operation quotes.
     */
    public function manageQuote(User $user, Operation $model): bool
    {
        return $user->can('operations.quote.manage');
    }

    /**
     * Determine whether the user can manage operation prescriptions.
     */
    public function managePrescription(User $user, Operation $model): bool
    {
        return $user->can('operations.prescription.manage');
    }

    /**
     * Determine whether the user can manage operation orders.
     */
    public function manageOrder(User $user, Operation $model): bool
    {
        return $user->can('operations.order.manage');
    }

    /**
     * Determine whether the user can manage operation productions.
     */
    public function manageProduction(User $user, Operation $model): bool
    {
        return $user->can('operations.production.manage');
    }

    /**
     * Determine whether the user can manage operation invoices.
     */
    public function manageInvoice(User $user, Operation $model): bool
    {
        return $user->can('operations.invoice.manage');
    }

    public function cancel(User $user, Operation $model): bool
    {
        return $user->can('operations.cancel');
    }

    public function archive(User $user, Operation $model): bool
    {
        return $user->can('operations.archive');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Operation $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Operation $model): bool
    {
        return false;
    }
}
