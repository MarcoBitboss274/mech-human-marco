<?php

namespace App\Http\Controllers;

use App\Enums\OperationStatusEnum;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceProfileRequest;
use App\Models\Operation;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SupplierService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class WorkspaceSupplierController extends Controller
{
    public function index()
    {
        return redirect()->route('workspace.supplier.dashboard');
    }

    public function orphan()
    {
        return Inertia::render('workspace/supplier/Orphan');
    }

    public function dashboard()
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.dashboard.view');

        return Inertia::render('workspace/supplier/Dashboard', [
            'supplier' => $this->supplierPayload(),
        ]);
    }

    public function profile()
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.profile.view');

        return Inertia::render('workspace/supplier/Profile', [
            'supplier' => $this->supplierPayload(),
        ]);
    }

    public function updateProfile(UpdateWorkspaceProfileRequest $request)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.profile.view');

        $user = UserService::currentUser();
        UserService::save($user, $request->validated());

        return to_route('workspace.supplier.profile.index');
    }

    public function settings()
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.settings.view');

        return Inertia::render('workspace/supplier/Settings', [
            'supplier' => $this->supplierPayload(true),
        ]);
    }

    public function updateSettings(StoreSupplierRequest $request)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.settings.update');

        $supplier = $this->currentSupplierOrFail();
        SupplierService::save($supplier, $request->validated());

        return back();
    }

    public function team()
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.team.view');

        $supplier = $this->currentSupplierOrFail();
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

        return Inertia::render('workspace/supplier/Team', [
            'supplier' => $this->supplierPayload(),
            'members' => $members,
        ]);
    }

    public function updateTeamMember(Request $request, User $user)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.team.manage');

        $data = $request->validate([
            'role' => 'required|string|in:admin,member',
        ]);

        $supplier = $this->currentSupplierOrFail();
        $this->ensureUserBelongsToSupplier($user, $supplier);

        SupplierService::updateUserRole($supplier, $user, $data['role']);

        return back();
    }

    public function removeTeamMember(User $user)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.team.manage');

        $supplier = $this->currentSupplierOrFail();
        $this->ensureUserBelongsToSupplier($user, $supplier);

        SupplierService::detachUser($supplier, $user);

        return back();
    }

    public function operationsIndex(Request $request)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.operations.view');

        $supplier = $this->currentSupplierOrFail();

        $query = Operation::query()
            ->whereHas('suppliers', fn (Builder $q) => $q->where('suppliers.id', $supplier->id))
            ->whereIn('status', [
                OperationStatusEnum::REQUESTED->value,
                OperationStatusEnum::IN_PROGRESS->value,
                OperationStatusEnum::WAITING_APPROVAL->value,
                OperationStatusEnum::PRODUCTION->value,
                OperationStatusEnum::COMPLETED->value,
            ])
            ->with([
                'latestPrescription:id,operation_id,ref,typology,send_at,expire_at',
                'building:id,name',
            ])
            ->latest();

        $search = $request->input('query');
        if (is_string($search) && trim($search) !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('latestPrescription', fn (Builder $p) => $p->where('ref', 'like', "%{$search}%"));
            });
        }

        $perPage = (int) ($request->input('per_page') ?? 25);

        return Inertia::render('workspace/supplier/operations/Index', [
            'supplier' => $this->supplierPayload(),
            'operations' => $query->paginate($perPage),
        ]);
    }

    public function operationsShow(Operation $operation)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.operations.view');

        $supplier = $this->currentSupplierOrFail();
        $belongs = $operation->suppliers()->where('suppliers.id', $supplier->id)->exists();
        abort_unless($belongs, 404);

        $operation->load([
            'latestPrescription:id,operation_id,ref,typology,send_at,expire_at',
            'building:id,name',
        ]);

        return Inertia::render('workspace/supplier/operations/Show', [
            'supplier' => $this->supplierPayload(),
            'operation' => $operation,
        ]);
    }

    private function currentSupplierOrFail(): Supplier
    {
        $user = UserService::currentUser();
        $supplier = $user?->suppliers()->first();
        abort_unless($supplier instanceof Supplier, 404);

        return $supplier;
    }

    private function supplierPayload(bool $withDetails = false): array
    {
        $supplier = $this->currentSupplierOrFail();
        $base = [
            'id' => $supplier->id,
            'name' => $supplier->name,
        ];

        if ($withDetails) {
            return array_merge($base, $supplier->only([
                'vat', 'mail', 'phone', 'address', 'cap', 'city', 'province', 'status',
            ]));
        }

        return $base;
    }

    private function ensureUserBelongsToSupplier(User $user, Supplier $supplier): void
    {
        $exists = $supplier->users()->where('users.id', $user->id)->exists();
        abort_unless($exists, 404);
    }
}
