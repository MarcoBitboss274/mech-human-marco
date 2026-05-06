<?php

namespace App\Services;

use App\Models\Building;
use App\Models\User;
use App\Notifications\User\Welcome;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class UserService extends ModelService
{
    /**
     * Get the class of the model
     */
    protected static function getClass(): string
    {
        return User::class;
    }

    /**
     * Fetch model
     */
    public static function fetch(?Request $request)
    {
        return static::getClass()::query()->where('email', '<>', 'admin@bitboss.it');
    }

    /**
     * Store model
     */
    public static function store(?Model $model, array $data = []): Model
    {
        $creating = is_null($model);

        $previousRole = $model?->role;

        $model = $model ?? static::newInstance();

        // Password
        if ($creating) {
            $data['password'] = self::generateRandomPassword();
        } else {
            if (is_null($data['password'])) {
                unset($data['password']);
            }
        }

        $model
            ->fill(
                Arr::only($data, Arr::except($model->getFillable(), ['role']))
            )
            ->save();

        // Role
        if ($data['role'] ?? false) {
            $model->assignRoleToUser($data['role']);
        }

        if ($creating) {
            if ($model->isAdmin()) {
                $model->verifyEmail();
            }

            // Send welcome email
            if ($data['send_invite'] ?? false) {
                self::sendWelcomeEmail($model);
            }
        }

        if (isset($data['verify_email'])) {
            $model->verifyEmail($data['verify_email']);
        }

        // Avatar
        if (isset($data['avatar'])) {
            $model->addMedia($data['avatar'])->toMediaCollection('avatar');
        }

        if (($data['role'] ?? null) === 'customer' && array_key_exists('building_relations', $data)) {
            $sync = [];
            foreach ($data['building_relations'] ?? [] as $rel) {
                $buildingId = $rel['building_id'] ?? null;
                $role = $rel['role'] ?? null;
                if ($buildingId && $role) {
                    $sync[(int) $buildingId] = ['role' => $role, 'accepted_at' => now()];
                }
            }
            $model->buildings()->sync($sync);
        }

        if (($data['role'] ?? null) === 'supplier' && array_key_exists('supplier_relation', $data)) {
            $rel = $data['supplier_relation'] ?? null;
            $supplierId = is_array($rel) ? ($rel['supplier_id'] ?? null) : null;
            $supplierRole = is_array($rel) ? ($rel['role'] ?? null) : null;

            if ($supplierId && $supplierRole) {
                $supplier = \App\Models\Supplier::query()->find((int) $supplierId);
                if ($supplier) {
                    SupplierService::attachUser($supplier, $model, (string) $supplierRole);
                }
            }
        } elseif ($previousRole === 'supplier' && ($data['role'] ?? null) !== 'supplier') {
            // Role changed away from supplier: detach from any team membership.
            $model->suppliers()->detach();
        }

        if (array_key_exists('managed_building_ids', $data) || (($data['role'] ?? null) !== 'agent' && $previousRole === 'agent')) {
            $managedBuildingIds = array_values(array_unique(array_filter(array_map(
                fn ($id) => is_numeric($id) ? (int) $id : null,
                $data['managed_building_ids'] ?? []
            ))));

            if (($data['role'] ?? null) === 'agent') {
                $currentManagedIds = $model->managedBuildings()->pluck('id')->map(fn ($id) => (int) $id)->all();

                $toDetach = array_values(array_diff($currentManagedIds, $managedBuildingIds));
                if (count($toDetach)) {
                    Building::query()
                        ->whereIn('id', $toDetach)
                        ->where('agent_id', $model->id)
                        ->update(['agent_id' => null]);
                }

                if (count($managedBuildingIds)) {
                    Building::query()
                        ->whereIn('id', $managedBuildingIds)
                        ->update(['agent_id' => $model->id]);
                }
            } elseif ($previousRole === 'agent') {
                Building::query()
                    ->where('agent_id', $model->id)
                    ->update(['agent_id' => null]);
            }
        }

        return $model;
    }

    /**
     * Apply search
     */
    public static function applySearch(Builder $query, ?Request $request): Builder
    {
        if ($request['query'] ?? false) {
            $searches = explode(' ', $request['query']);

            if (count($searches) > 1) {
                $query->where(function ($query) use ($searches) {
                    $query->where(function ($query) use ($searches) {
                        $query
                            ->where('name', 'like', "%{$searches[0]}%")
                            ->where('surname', 'like', "%{$searches[1]}%");
                    })->orWhere(function ($query) use ($searches) {
                        $query
                            ->where('name', 'like', "%{$searches[1]}%")
                            ->where('surname', 'like', "%{$searches[0]}%");
                    });
                });
            } else {
                QueryService::querySearch($query, $request['query'] ?? null, ['name', 'surname', 'email']);
            }
        }

        return $query;
    }

    /**
     * Apply filters
     */
    public static function applyFilters(Builder $query, ?Request $request): Builder
    {
        $role = $request?->input('role');
        if ($role !== null && $role !== '') {
            $value = is_array($role) ? ($role[0] ?? null) : $role;
            if ($value !== null && $value !== '') {
                $query->where('role', $value);
            }
        }

        $buildingId = $request?->input('building_id');
        if ($buildingId !== null && $buildingId !== '') {
            $id = (int) $buildingId;
            if ($id > 0) {
                $query->where(function (Builder $q) use ($id) {
                    $q->whereHas('buildings', function (Builder $b) use ($id) {
                        $b->where('buildings.id', $id);
                    })->orWhereHas('managedBuildings', function (Builder $b) use ($id) {
                        $b->where('buildings.id', $id);
                    });
                });
            }
        }

        $active = $request?->input('active');
        if ($active !== null && $active !== '') {
            $query->where('active', $active === '1' || $active === 1 || $active === true);
        }

        return $query;
    }

    /**
     * Apply sorts
     */
    public static function applySorts(Builder $query, ?Request $request): Builder
    {
        return $query->latest();
    }

    /**
     * Get the current user
     */
    public static function currentUser(): User
    {
        return User::find(Auth::id());
    }

    /**
     * Send welcome email
     */
    public static function sendWelcomeEmail(User $user)
    {
        app('auth.password.broker')->deleteToken($user);
        $url = route('password.reset', [
            'email' => $user->email,
            'token' => app('auth.password.broker')->createToken($user),
        ]);
        $user->notify(new Welcome($url));
        $user->update(['invited_at' => now()]);
    }
}
