<?php

namespace App\Policies;

use App\Models\Building;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BuildingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('buildings.index');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Building $model): bool
    {
        return $user->can('buildings.index');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('buildings.create');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Building $model): bool
    {
        return $user->can('buildings.edit');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Building $model): bool
    {
        return $user->can('buildings.destroy');
    }

    /**
     * Determine whether the user can invite building members.
     */
    public function inviteMember(User $user, Building $model): bool
    {
        return $user->can('buildings.members.invite');
    }

    /**
     * Determine whether the user can edit building members.
     */
    public function editMember(User $user, Building $model): bool
    {
        return $user->can('buildings.members.edit');
    }

    /**
     * Determine whether the user can delete building members.
     */
    public function deleteMember(User $user, Building $model): bool
    {
        return $user->can('buildings.members.delete');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Building $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Building $model): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the my buildings.
     */
    public function viewMy(User $user): bool
    {
        return $user->isAgent();
    }
}