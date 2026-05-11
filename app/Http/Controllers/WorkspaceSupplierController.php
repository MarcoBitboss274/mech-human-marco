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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class WorkspaceSupplierController extends Controller
{
    public function index()
    {
        return redirect()->route('workspace.supplier.operations.index');
    }

    public function orphan()
    {
        return Inertia::render('workspace/supplier/Orphan');
    }

    public function dashboard()
    {
        return redirect()->route('workspace.supplier.operations.index');
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
            $q->select('users.id', 'users.name', 'users.surname', 'users.email', 'users.created_at')
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
            'created_at' => $u->created_at?->toISOString(),
            'accepted_at' => $u->pivot->accepted_at ? Carbon::parse($u->pivot->accepted_at)->toISOString() : null,
            'pivot' => [
                'role' => $u->pivot->role ?? null,
            ],
        ])->values();

        return Inertia::render('workspace/supplier/Team', [
            'supplier' => $this->supplierPayload(),
            'members' => $members,
        ]);
    }

    public function inviteTeamMember(Request $request)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.team.manage');

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|string|in:admin,member',
        ]);

        $supplier = $this->currentSupplierOrFail();
        SupplierService::invite($supplier, $data['email'], $data['role']);

        return back();
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
            ->select('operations.*')
            ->whereHas('suppliers', fn (Builder $q) => $q
                ->where('suppliers.id', $supplier->id)
                ->where('operation_supplier.selected', true))
            ->whereIn('operations.status', [
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
            ->withSupplierPivot($supplier->id)
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
            $this->applySupplierVisibleStatusFilter($query, $supplierStatus, $supplier->id);
        }

        $canceledState = $request->input('canceled_state');
        if ($canceledState === 'only') {
            $query->whereNotNull('operations.canceled_at');
        } elseif ($canceledState === 'excluded') {
            $query->whereNull('operations.canceled_at');
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
            ->orderBy('operations.id', 'desc');

        $perPage = (int) ($request->input('per_page') ?? 25);

        return Inertia::render('workspace/supplier/operations/Index', [
            'supplier' => $this->supplierPayload(),
            'operations' => $query->paginate($perPage),
            'filters' => [
                'query' => $search,
                'ref' => $ref,
                'batch_number' => $batch,
                'supplier_visible_status' => $supplierStatus,
                'canceled_state' => $canceledState,
                'documents_state' => $documentsState,
            ],
        ]);
    }

    /**
     * Filtro per i 3 stati base visibili al fornitore. `Annullata` è un flag ortogonale,
     * gestito a parte tramite il filtro `canceled_state`.
     */
    private function applySupplierVisibleStatusFilter(Builder $query, string $status, int $supplierId): void
    {
        $newCaseStatuses = [
            OperationStatusEnum::REQUESTED->value,
            OperationStatusEnum::IN_PROGRESS->value,
            OperationStatusEnum::WAITING_APPROVAL->value,
        ];

        $productionConfirmedStatuses = [
            OperationStatusEnum::PRODUCTION->value,
            OperationStatusEnum::COMPLETED->value,
        ];

        $pivotCompletedQuery = fn () => DB::table('operation_supplier')
            ->whereColumn('operation_supplier.operation_id', 'operations.id')
            ->where('operation_supplier.supplier_id', $supplierId)
            ->where('operation_supplier.selected', true)
            ->whereNotNull('operation_supplier.supplier_completed_at');

        match ($status) {
            'new_case' => $query
                ->whereIn('operations.status', $newCaseStatuses)
                ->whereNotExists($pivotCompletedQuery()),
            'production_confirmed' => $query
                ->whereIn('operations.status', $productionConfirmedStatuses)
                ->whereNotExists($pivotCompletedQuery()),
            'completed' => $query->whereExists($pivotCompletedQuery()),
            default => null,
        };
    }

    public function operationsShow(Operation $operation)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.operations.view');

        $supplier = $this->ensureOperationBelongsToCurrentSupplier($operation);

        // Esponi `supplier_completed_at` dal pivot per il calcolo dello stato visibile lato Vue.
        $supplierCompletedAt = DB::table('operation_supplier')
            ->where('operation_id', $operation->id)
            ->where('supplier_id', $supplier->id)
            ->where('selected', true)
            ->value('supplier_completed_at');
        $operation->setAttribute('supplier_completed_at', $supplierCompletedAt);

        $operation->load([
            'latestPrescription:id,operation_id,ref,typology,send_at,expire_at',
            'latestPrescription.activeRevision',
            'building:id,name',
            'media',
            'prescriptions' => fn ($q) => $q->latest()->with([
                'user:id,name,surname,email',
                'building:id,name',
                'protrusorDetails',
                'lybraAlignerDetails',
                'guidedSurgeryDetails',
                'threeDMeshDetails',
                'prosthesisDetails',
                'semiFinishedProsthesisDetails',
                'activeRevision.reasons',
            ]),
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

        OperationService::uploadSupplierDocument($operation, $request->file('file'), $request->user(), asAdmin: false);

        return back();
    }

    public function operationsDeleteDocument(Operation $operation, Media $media)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.operations.documents.manage');

        $this->ensureOperationBelongsToCurrentSupplier($operation);

        OperationService::deleteSupplierDocument($operation, $media, asAdmin: false);

        return back();
    }

    public function operationsMarkCompleted(Operation $operation)
    {
        Gate::authorize('supplierWorkspaceAbility', 'workspace.supplier.operations.complete');

        $supplier = $this->ensureOperationBelongsToCurrentSupplier($operation);

        OperationService::markSupplierProductionCompleted($operation, $supplier, UserService::currentUser());

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
