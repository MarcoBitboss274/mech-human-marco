<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SupplierService;
use App\Services\UserService;
use App\Http\Requests\Supplier\InviteSupplierMemberRequest;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('suppliers/Index', [
            'suppliers' => SupplierService::search($request, true),
        ]);
    }

    /**
     * Show a single supplier with its team members.
     */
    public function show(Supplier $supplier)
    {
        $supplier->load(['users' => function ($q) {
            $q->select('users.id', 'users.name', 'users.surname', 'users.email', 'users.last_login_at')
                ->orderBy('users.surname')
                ->orderBy('users.name');
        }]);

        $members = $supplier->users->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'surname' => $u->surname,
            'full_name' => trim(($u->name ?? '') . ' ' . ($u->surname ?? '')),
            'email' => $u->email,
            'role' => $u->pivot->role ?? null,
            'status' => $u->last_login_at !== null ? 'active' : 'pending',
            'last_login_at' => $u->last_login_at?->toISOString(),
        ])->values();

        return Inertia::render('suppliers/Show', [
            'supplier' => $supplier->only([
                'id', 'name', 'vat', 'mail', 'phone', 'address', 'cap', 'city', 'province', 'status',
            ]),
            'members' => $members,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        SupplierService::save(null, $request->validated());

        return to_route('suppliers.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreSupplierRequest $request, Supplier $supplier)
    {
        SupplierService::save($supplier, $request->validated());

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Supplier $supplier)
    {
        SupplierService::delete($supplier);

        return to_route('suppliers.index');
    }

    /**
     * Invite a member by email: if a user with that email already exists it must be free
     * (no other supplier team), otherwise we create a fresh supplier user on the fly with
     * the chosen role and bind it to this supplier. Used by the "Invita membro" inline
     * dialog in the supplier detail page.
     */
    public function inviteMember(InviteSupplierMemberRequest $request, Supplier $supplier)
    {
        $email = (string) $request->input('email');
        $role = (string) $request->input('role');

        $existing = User::query()->where('email', $email)->first();

        if ($existing !== null) {
            if ($existing->role !== RoleEnum::SUPPLIER->value) {
                throw ValidationException::withMessages([
                    'email' => 'Esiste già un utente con questa email ma con un ruolo diverso.',
                ]);
            }

            $alreadyAttached = $supplier->users()->where('users.id', $existing->id)->exists();
            if ($alreadyAttached) {
                throw ValidationException::withMessages([
                    'email' => 'Questo utente è già membro del team di questo fornitore.',
                ]);
            }

            $otherSupplierId = \DB::table('supplier_user')->where('user_id', $existing->id)->value('supplier_id');
            if ($otherSupplierId !== null && (int) $otherSupplierId !== $supplier->id) {
                throw ValidationException::withMessages([
                    'email' => 'Questo utente è già associato a un altro fornitore.',
                ]);
            }

            SupplierService::attachUser($supplier, $existing, $role);

            return back();
        }

        // Crea l'utente al volo: nome/cognome vuoti, M&H li popolerà in seguito se necessario.
        $newUser = UserService::store(null, [
            'name' => '',
            'surname' => '',
            'email' => $email,
            'role' => RoleEnum::SUPPLIER->value,
        ]);

        SupplierService::attachUser($supplier, $newUser, $role);

        return back();
    }

    /**
     * Attach an existing user to a supplier team. Used as a fallback when M&H wants to
     * associate an already-existing user without recreating it.
     */
    public function attachMember(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|string|in:admin,member',
        ]);

        $user = User::query()->findOrFail($data['user_id']);
        SupplierService::attachUser($supplier, $user, $data['role']);

        return back();
    }

    /**
     * Update the role (admin/member) of a team member.
     */
    public function updateMember(Request $request, Supplier $supplier, User $user)
    {
        $data = $request->validate([
            'role' => 'required|string|in:admin,member',
        ]);

        SupplierService::updateUserRole($supplier, $user, $data['role']);

        return back();
    }

    /**
     * Remove a user from the supplier team. The user record itself is preserved (becomes "orphan").
     */
    public function detachMember(Supplier $supplier, User $user)
    {
        SupplierService::detachUser($supplier, $user);

        return back();
    }
}
