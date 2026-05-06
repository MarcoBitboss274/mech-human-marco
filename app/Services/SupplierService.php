<?php

namespace App\Services;

use App\Enums\SupplierUserRoleEnum;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class SupplierService extends ModelService
{
    /**
     * Get the class of the model
     */
    protected static function getClass(): string
    {
        return Supplier::class;
    }

    /**
     * Apply search
     */
    public static function applySearch(Builder $query, Request|null $request): Builder
    {
        if ($request['query'] ?? false) {
            QueryService::querySearch($query, $request['query'], ['name', 'vat', 'mail', 'city']);
        }

        return $query;
    }

    /**
     * Apply sorts
     */
    public static function applySorts(Builder $query, Request|null $request): Builder
    {
        return $query->latest();
    }

    /**
     * Store model
     */
    public static function store(Model|null $model, array $data = []): Model
    {
        $model = $model ?? static::newInstance();
        $model->fill(Arr::only($data, $model->getFillable()));
        $model->save();

        return $model;
    }

    /**
     * Attach a user to the supplier team with the given role, replacing any previous association.
     * Enforces uniqueness (1 user ↔ 1 supplier) and the "at least 1 admin" rule.
     */
    public static function attachUser(Supplier $supplier, User $user, string $role): void
    {
        self::assertValidRole($role);

        DB::transaction(function () use ($supplier, $user, $role) {
            // Detach the user from any previous supplier (the unique key on user_id enforces this anyway).
            $previousSupplierId = DB::table('supplier_user')->where('user_id', $user->id)->value('supplier_id');
            if ($previousSupplierId !== null && (int) $previousSupplierId !== $supplier->id) {
                $previousSupplier = Supplier::query()->find($previousSupplierId);
                if ($previousSupplier) {
                    self::assertCanRemoveAdmin($previousSupplier, $user);
                }
                DB::table('supplier_user')->where('user_id', $user->id)->delete();
            }

            $supplier->users()->syncWithoutDetaching([
                $user->id => ['role' => $role, 'updated_at' => now(), 'created_at' => now()],
            ]);
            // Ensure the role is updated even if the row already existed.
            $supplier->users()->updateExistingPivot($user->id, ['role' => $role]);
        });
    }

    /**
     * Detach a user from the supplier team (soft from a domain perspective: pivot row is deleted).
     */
    public static function detachUser(Supplier $supplier, User $user): void
    {
        self::assertCanRemoveAdmin($supplier, $user);
        $supplier->users()->detach($user->id);
    }

    /**
     * Update the role (admin/member) of an already-attached team member.
     */
    public static function updateUserRole(Supplier $supplier, User $user, string $newRole): void
    {
        self::assertValidRole($newRole);

        $currentRole = $supplier->users()->where('users.id', $user->id)->first()?->pivot?->role;
        if ($currentRole === null) {
            throw ValidationException::withMessages([
                'user' => 'L\'utente non fa parte del team di questo fornitore.',
            ]);
        }

        if ($currentRole === SupplierUserRoleEnum::ADMIN->value && $newRole !== SupplierUserRoleEnum::ADMIN->value) {
            self::assertCanRemoveAdmin($supplier, $user);
        }

        $supplier->users()->updateExistingPivot($user->id, ['role' => $newRole]);
    }

    /**
     * Block downgrade or removal of the last admin of a supplier.
     */
    private static function assertCanRemoveAdmin(Supplier $supplier, User $user): void
    {
        $userRole = $supplier->users()->where('users.id', $user->id)->first()?->pivot?->role;
        if ($userRole !== SupplierUserRoleEnum::ADMIN->value) {
            return;
        }

        $remainingAdmins = $supplier->users()
            ->wherePivot('role', SupplierUserRoleEnum::ADMIN->value)
            ->where('users.id', '!=', $user->id)
            ->count();

        if ($remainingAdmins === 0) {
            throw ValidationException::withMessages([
                'role' => 'Il fornitore deve avere almeno un Admin. Promuovi prima un altro membro ad Admin.',
            ]);
        }
    }

    private static function assertValidRole(string $role): void
    {
        if (SupplierUserRoleEnum::tryFrom($role) === null) {
            throw ValidationException::withMessages([
                'role' => 'Ruolo non valido. Valori ammessi: admin, member.',
            ]);
        }
    }
}
