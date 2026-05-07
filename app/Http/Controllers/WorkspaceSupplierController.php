<?php

namespace App\Http\Controllers;

use App\Enums\OperationStatusEnum;
use App\Http\Requests\Operation\UploadOperationSupplierDocumentRequest;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Workspace\UpdateWorkspaceProfileRequest;
use App\Models\Operation;
use App\Models\Supplier;
use App\Models\User;
use App\Services\OperationService;
use App\Services\SupplierService;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
            ->whereHas('suppliers', fn (Builder $q) => $q
                ->where('suppliers.id', $supplier->id)
                ->where('operation_supplier.selected', true))
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
            ->withCount(['media as supplier_documents_count' => fn ($q) => $q->where('collection_name', 'supplier_documents')])
            ->addSelect([
                'assigned_at' => DB::table('operation_supplier')
                    ->select('selected_at')
                    ->whereColumn('operation_supplier.operation_id', 'operations.id')
                    ->where('operation_supplier.supplier_id', $supplier->id)
                    ->where('operation_supplier.selected', true)
                    ->limit(1),
            ]);

        $search = $request->input('query');
        if (is_string($search) && trim($search) !== '') {
            $query->where(function (Builder $q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                    ->orWhereHas('latestPrescription', fn (Builder $p) => $p->where('ref', 'like', "%{$search}%"));
            });
        }

        $ref = $request->input('ref');
        if (is_string($ref) && trim($ref) !== '') {
            $query->whereHas('latestPrescription', fn (Builder $p) => $p->where('ref', 'like', "%{$ref}%"));
        }

        $batch = $request->input('batch_number');
        if (is_string($batch) && trim($batch) !== '') {
            $query->where('batch_number', 'like', "%{$batch}%");
        }

        $supplierStatus = $request->input('supplier_visible_status');
        if (is_string($supplierStatus) && $supplierStatus !== '') {
            $this->applySupplierVisibleStatusFilter($query, $supplierStatus);
        }

        $documentsState = $request->input('documents_state');
        if ($documentsState === 'uploaded') {
            $query->whereHas('media', fn (Builder $m) => $m->where('collection_name', 'supplier_documents'));
        } elseif ($documentsState === 'missing') {
            $query->whereDoesntHave('media', fn (Builder $m) => $m->where('collection_name', 'supplier_documents'));
        }

        $query->leftJoin('prescriptions', function ($join) {
            $join->on('prescriptions.operation_id', '=', 'operations.id')
                ->whereRaw('prescriptions.id = (select max(id) from prescriptions where prescriptions.operation_id = operations.id)');
        })
            ->orderByRaw('prescriptions.expire_at IS NULL ASC')
            ->orderBy('prescriptions.expire_at', 'asc')
            ->orderBy('operations.id', 'desc')
            ->select('operations.*');

        $perPage = (int) ($request->input('per_page') ?? 25);

        return Inertia::render('workspace/supplier/operations/Index', [
            'supplier' => $this->supplierPayload(),
            'operations' => $query->paginate($perPage),
            'filters' => [
                'query' => $search,
                'ref' => $ref,
                'batch_number' => $batch,
                'supplier_visible_status' => $supplierStatus,
                'documents_state' => $documentsState,
            ],
        ]);
    }

    private function applySupplierVisibleStatusFilter(Builder $query, string $status): void
    {
        // Lo stato visibile è derivato: lo traduciamo in condizioni su status + canceled_at + presenza media.
        match ($status) {
            'canceled' => $query->whereNotNull('canceled_at'),
            'assigned_waiting_documents' => $query
                ->whereNull('canceled_at')
                ->where('operations.status', OperationStatusEnum::REQUESTED->value)
                ->whereDoesntHave('media', fn (Builder $m) => $m->where('collection_name', 'supplier_documents')),
            'documents_sent' => $query
                ->whereNull('canceled_at')
                ->where('operations.status', OperationStatusEnum::REQUESTED->value)
                ->whereHas('media', fn (Builder $m) => $m->where('collection_name', 'supplier_documents')),
            'under_evaluation' => $query
                ->whereNull('canceled_at')
                ->whereIn('operations.status', [
                    OperationStatusEnum::IN_PROGRESS->value,
                    OperationStatusEnum::WAITING_APPROVAL->value,
                ]),
            'production_confirmed' => $query
                ->whereNull('canceled_at')
                ->where('operations.status', OperationStatusEnum::PRODUCTION->value),
            'completed' => $query
                ->whereNull('canceled_at')
                ->where('operations.status', OperationStatusEnum::COMPLETED->value),
            default => null,
        };
    }

    public function operationsShow(Operation $operation)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.operations.view');

        $supplier = $this->ensureOperationBelongsToCurrentSupplier($operation);

        $operation->load([
            'latestPrescription:id,operation_id,ref,typology,send_at,expire_at',
            'building:id,name',
            'media',
        ]);

        return Inertia::render('workspace/supplier/operations/Show', [
            'supplier' => $this->supplierPayload(),
            'operation' => $operation,
            'supplier_documents' => $this->mapSupplierDocuments($operation),
        ]);
    }

    public function operationsUploadDocument(UploadOperationSupplierDocumentRequest $request, Operation $operation)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.operations.documents.manage');

        $this->ensureOperationBelongsToCurrentSupplier($operation);

        OperationService::uploadSupplierDocument($operation, $request->file('file'), $request->user());

        return back();
    }

    public function operationsDeleteDocument(Operation $operation, Media $media)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.operations.documents.manage');

        $this->ensureOperationBelongsToCurrentSupplier($operation);

        OperationService::deleteSupplierDocument($operation, $media, asAdmin: false);

        return back();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function mapSupplierDocuments(Operation $operation): array
    {
        return $operation->getMedia('supplier_documents')->map(function (Media $media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'file_name' => $media->file_name,
                'size' => $media->size,
                'uploaded_at' => $media->getCustomProperty('uploaded_at') ?? $media->created_at?->toIso8601String(),
                'uploaded_by' => $this->resolveUploaderName((int) $media->getCustomProperty('uploaded_by_user_id')),
                'url' => route('media.index', ['media' => $media->id]),
            ];
        })->values()->all();
    }

    private function resolveUploaderName(?int $userId): ?string
    {
        if (! $userId) {
            return null;
        }
        $user = User::query()->find($userId);
        if (! $user) {
            return null;
        }

        return trim(($user->name ?? '') . ' ' . ($user->surname ?? '')) ?: $user->email;
    }

    private function ensureOperationBelongsToCurrentSupplier(Operation $operation): Supplier
    {
        $supplier = $this->currentSupplierOrFail();
        $belongs = $operation->suppliers()
            ->wherePivot('selected', true)
            ->where('suppliers.id', $supplier->id)
            ->exists();
        abort_unless($belongs, 403);

        return $supplier;
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
