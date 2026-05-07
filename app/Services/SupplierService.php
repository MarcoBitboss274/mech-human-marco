<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Enums\SupplierUserRoleEnum;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\Supplier\Invite;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
                $user->id => [
                    'role' => $role,
                    'accepted_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            ]);
            // Ensure the role is updated even if the row already existed.
            $supplier->users()->updateExistingPivot($user->id, ['role' => $role]);
        });
    }

    /**
     * Invite a member to the supplier team by email. Specchio di
     * WorkspaceService::inviteBuildingMember: crea l'utente se non esiste, attacca
     * il pivot con invite_token e accepted_at=null, manda la mail di invito.
     */
    public static function invite(Supplier $supplier, string $email, string $role): User
    {
        self::assertValidRole($role);

        $existing = User::query()->where('email', $email)->first();

        if ($existing && $supplier->users()->where('users.id', $existing->id)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Questo utente è già membro del team di questo fornitore.'],
            ]);
        }

        if ($existing && $existing->role !== RoleEnum::SUPPLIER->value) {
            throw ValidationException::withMessages([
                'email' => ['Esiste già un utente con questa email ma con un ruolo diverso.'],
            ]);
        }

        if ($existing) {
            $otherSupplierId = DB::table('supplier_user')->where('user_id', $existing->id)->value('supplier_id');
            if ($otherSupplierId !== null && (int) $otherSupplierId !== $supplier->id) {
                throw ValidationException::withMessages([
                    'email' => ['Questo utente è già associato a un altro fornitore.'],
                ]);
            }
        }

        $inviteToken = Str::uuid()->toString();
        $isNew = $existing === null;

        if ($existing) {
            $user = $existing;
        } else {
            $user = UserService::store(null, [
                'name' => '',
                'surname' => '',
                'email' => $email,
                'role' => RoleEnum::SUPPLIER->value,
            ]);
        }

        $supplier->users()->attach($user->id, [
            'role' => $role,
            'is_new' => $isNew,
            'invite_token' => $inviteToken,
        ]);

        $url = route('invitation.index', ['token' => $inviteToken]);
        $user->notify(new Invite($supplier, $url));

        return $user;
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
