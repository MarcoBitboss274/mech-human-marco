<?php

namespace App\Http\Controllers;

use App\Enums\RoleEnum;
use App\Http\Requests\Operation\AddOperationInvoiceRequest;
use App\Http\Requests\Operation\AddOperationOrderRequest;
use App\Http\Requests\Operation\AddOperationQuoteRequest;
use App\Http\Requests\Operation\AddOperationSupplierRequest;
use App\Http\Requests\Operation\RemoveOperationInvoiceRequest;
use App\Http\Requests\Operation\RemoveOperationOrderRequest;
use App\Http\Requests\Operation\RemoveOperationQuoteRequest;
use App\Http\Requests\Operation\SelectOperationSupplierRequest;
use App\Http\Requests\Operation\StoreOperationRequest;
use App\Http\Requests\Operation\SwapOperationSupplierRequest;
use App\Http\Requests\Operation\StoreOperationWithPrescriptionRequest;
use App\Http\Requests\Operation\UpdateOperationInvoiceRequest;
use App\Http\Requests\Operation\UpdateOperationInvoiceStatusRequest;
use App\Http\Requests\Operation\UpdateOperationOrderRequest;
use App\Http\Requests\Operation\UpdateOperationOrderStatusRequest;
use App\Http\Requests\Operation\UpdateOperationPrescriptionRequest;
use App\Http\Requests\Operation\UpdateOperationQuoteRequest;
use App\Http\Requests\Operation\UpdateOperationQuoteStatusRequest;
use App\Http\Requests\Operation\UpdateOperationStatusRequest;
use App\Http\Requests\Operation\UpdateOperationSupplierStatusRequest;
use App\Http\Requests\Operation\UpdateOperationWithPrescriptionRequest;
use App\Exports\OperationsExport;
use App\Models\Invoice;
use App\Models\Operation;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Quote;
use App\Models\Supplier;
use App\Policies\OperationChatPolicy;
use App\Services\OperationService;
use App\Services\PrescriptionService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;

class OperationController extends Controller
{
    /**
     * Display the create wizard page.
     */
    public function create()
    {
        return Inertia::render('operations/Create', [
            'wizard' => [
                'mode' => 'create',
                'operationId' => null,
                'prescriptionId' => null,
                'initialForm' => null,
                'buildings' => OperationService::getWizardBuildingsPayload(),
            ],
        ]);
    }

    /**
     * Store operation with linked prescription (wizard flow).
     */
    public function storeWithPrescription(StoreOperationWithPrescriptionRequest $request)
    {
        $operation = OperationService::createWithPrescription($request->validated());

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Display the edit wizard page for operation and prescription.
     */
    public function editWithPrescription(Operation $operation)
    {
        Gate::authorize('update', $operation);
        Gate::authorize('managePrescription', $operation);

        return Inertia::render('operations/Create', [
            'wizard' => OperationService::getEditWizardData($operation),
        ]);
    }

    /**
     * Update operation with linked prescription (wizard flow).
     */
    public function updateWithPrescription(UpdateOperationWithPrescriptionRequest $request, Operation $operation)
    {
        Gate::authorize('update', $operation);
        Gate::authorize('managePrescription', $operation);

        OperationService::updateWithPrescription($operation, $request->validated());

        if ($request->boolean('submit_revision')) {
            $prescription = $operation->latestPrescription()->first();
            if ($prescription !== null) {
                PrescriptionService::sendPrescription($prescription, $request->user());
            }
        }

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $operations = OperationService::search($request, true, [
            'latestPrescription' => fn($q) => $q
                ->select(['id', 'operation_id', 'user_id', 'typology', 'ref', 'created_at', 'expire_at', 'send_at'])
                ->with(['user:id,name,surname', 'activeRevision:id,prescription_id,opened_at,closed_at']),
        ]);

        $total = $operations->total();

        return Inertia::render('operations/Index', [
            'operations' => $operations,
            'resultsLabel' => OperationService::buildResultsLabel($request, $total),
            'resultsTotal' => $total,
        ]);
    }

    /**
     * Export the filtered operations (current tab + filters) to CSV or Excel.
     */
    public function export(Request $request)
    {
        $format = $this->resolveExportFormat($request);

        $query = OperationService::fetch($request);
        OperationService::applySearch($query, $request);
        OperationService::applyFilters($query, $request);

        $total = (clone $query)->toBase()->getCountForPagination();
        $this->ensureExportSizeWithinLimit($total);

        $tabSlug = $this->resolveTabSlug($request->input('mode'));
        $filename = 'lavorazioni_' . $tabSlug . '_' . now()->format('Ymd_His') . '.' . $format['extension'];

        return Excel::download(new OperationsExport($query, false), $filename, $format['writerType']);
    }

    /**
     * Export every operation in the platform (all tabs, ignoring filters) to CSV or Excel.
     */
    public function exportAll(Request $request)
    {
        $format = $this->resolveExportFormat($request);

        $query = Operation::query();

        $total = (clone $query)->toBase()->getCountForPagination();
        $this->ensureExportSizeWithinLimit($total);

        $filename = 'lavorazioni_tutte_' . now()->format('Ymd_His') . '.' . $format['extension'];

        return Excel::download(new OperationsExport($query, true), $filename, $format['writerType']);
    }

    /**
     * Resolve the requested export format ("csv" or "xlsx") into Maatwebsite writer type + extension.
     *
     * @return array{writerType: string, extension: string}
     */
    private function resolveExportFormat(Request $request): array
    {
        $format = strtolower((string) $request->input('format', 'xlsx'));

        return match ($format) {
            'csv' => ['writerType' => ExcelFormat::CSV, 'extension' => 'csv'],
            'xlsx' => ['writerType' => ExcelFormat::XLSX, 'extension' => 'xlsx'],
            default => throw ValidationException::withMessages([
                'format' => 'Formato non supportato. Usa csv o xlsx.',
            ]),
        };
    }

    /**
     * Hard limit to keep sync exports manageable. Beyond this threshold the request is rejected.
     */
    private function ensureExportSizeWithinLimit(int $total): void
    {
        if ($total > 50000) {
            throw ValidationException::withMessages([
                'export' => 'Troppi risultati per l\'esportazione (limite 50.000). Restringi i filtri.',
            ]);
        }
    }

    private function resolveTabSlug(mixed $mode): string
    {
        $value = is_array($mode) ? ($mode[0] ?? null) : $mode;
        return match ((string) $value) {
            'active' => 'attive',
            'archived' => 'archiviate',
            default => 'bozze',
        };
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Operation $operation)
    {
        Gate::authorize('view', $operation);
        $user = UserService::currentUser() ?? abort(401);

        return Inertia::render('operations/Show', [
            ...(match ($user->role) {
                RoleEnum::ADMIN->value, RoleEnum::SUPERADMIN->value => OperationService::getAdminShowData($operation),
                RoleEnum::AGENT->value => OperationService::getAgentShowData($operation),
                default => [],
            } ?? []),
            'chat' => [
                'can_read' => app(OperationChatPolicy::class)->viewMessages($user, $operation),
                'can_send' => app(OperationChatPolicy::class)->sendMessage($user, $operation),
                'can_read_supplier' => app(\App\Policies\OperationSupplierChatPolicy::class)->viewMessages($user, $operation),
                'can_send_supplier' => app(\App\Policies\OperationSupplierChatPolicy::class)->sendMessage($user, $operation),
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOperationRequest $request)
    {
        OperationService::save(null, $request->validated());

        return to_route('operations.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreOperationRequest $request, Operation $operation)
    {
        OperationService::save($operation, $request->validated());

        return to_route('operations.index');
    }

    /**
     * Update the operation status.
     */
    public function updateStatus(UpdateOperationStatusRequest $request, Operation $operation)
    {
        Gate::authorize('update', $operation);

        OperationService::updateStatus($operation, $request->input('status'), true);

        return back();
    }

    public function cancel(Operation $operation)
    {
        Gate::authorize('cancel', $operation);

        /** @noinspection PhpUndefinedMethodInspection */
        OperationService::cancel($operation);

        return back();
    }

    public function reactivate(Operation $operation)
    {
        Gate::authorize('cancel', $operation);

        /** @noinspection PhpUndefinedMethodInspection */
        OperationService::reactivate($operation);

        return back();
    }

    public function archive(Operation $operation)
    {
        Gate::authorize('archive', $operation);

        /** @noinspection PhpUndefinedMethodInspection */
        OperationService::archive($operation);

        return back();
    }

    public function reopen(Operation $operation)
    {
        Gate::authorize('archive', $operation);

        /** @noinspection PhpUndefinedMethodInspection */
        OperationService::reopen($operation);

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Operation $operation)
    {
        OperationService::delete($operation);

        return to_route('operations.index');
    }

    /**
     * Attach a supplier to the specified operation.
     */
    public function addSupplier(AddOperationSupplierRequest $request, Operation $operation)
    {
        Gate::authorize('manageSupplier', $operation);

        $supplier = Supplier::query()->findOrFail($request->integer('supplier_id'));
        OperationService::attachSupplier($operation, $supplier);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Mark one supplier as selected for the specified operation.
     */
    public function selectSupplier(SelectOperationSupplierRequest $request, Operation $operation)
    {
        Gate::authorize('manageSupplier', $operation);

        $supplier = Supplier::query()->findOrFail($request->integer('supplier_id'));
        OperationService::selectSupplier($operation, $supplier);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Cambio del fornitore selezionato (hard delete del precedente + attach del nuovo).
     */
    public function swapSupplier(SwapOperationSupplierRequest $request, Operation $operation)
    {
        Gate::authorize('manageSupplier', $operation);

        $supplier = Supplier::query()->findOrFail($request->integer('supplier_id'));
        OperationService::swapSupplier($operation, $supplier);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Remove a supplier from the specified operation.
     */
    public function removeSupplier(Operation $operation, Supplier $supplier)
    {
        Gate::authorize('manageSupplier', $operation);

        OperationService::detachSupplier($operation, $supplier);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Update the status for one supplier in the operation pivot.
     */
    public function updateSupplierStatus(UpdateOperationSupplierStatusRequest $request, Operation $operation)
    {
        Gate::authorize('manageSupplier', $operation);

        $supplier = Supplier::query()->findOrFail($request->integer('supplier_id'));
        OperationService::updateSupplierStatus($operation, $supplier, $request->input('status'));

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Update one prescription for the specified operation.
     */
    public function updatePrescription(UpdateOperationPrescriptionRequest $request, Operation $operation, Prescription $prescription)
    {
        Gate::authorize('managePrescription', $operation);

        OperationService::updatePrescription($operation, $prescription, $request->validated());

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Add a quote to the specified operation.
     */
    public function addQuote(AddOperationQuoteRequest $request, Operation $operation)
    {
        Gate::authorize('manageQuote', $operation);

        OperationService::createQuote($operation, $request->validated());

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Update one quote for the specified operation.
     */
    public function updateQuote(UpdateOperationQuoteRequest $request, Operation $operation, Quote $quote)
    {
        Gate::authorize('manageQuote', $operation);

        OperationService::updateQuote($operation, $quote, $request->validated());

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Remove one quote from the specified operation.
     */
    public function removeQuote(RemoveOperationQuoteRequest $request, Operation $operation, Quote $quote)
    {
        Gate::authorize('manageQuote', $operation);

        OperationService::deleteQuote($operation, $quote);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Update status for one quote in the specified operation.
     */
    public function updateQuoteStatus(UpdateOperationQuoteStatusRequest $request, Operation $operation, Quote $quote)
    {
        Gate::authorize('manageQuote', $operation);

        OperationService::updateQuoteStatus($operation, $quote, $request->input('status'));

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Add an order to the specified operation.
     */
    public function addOrder(AddOperationOrderRequest $request, Operation $operation)
    {
        Gate::authorize('manageOrder', $operation);

        OperationService::createOrder($operation, $request->validated());

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Update one order for the specified operation.
     */
    public function updateOrder(UpdateOperationOrderRequest $request, Operation $operation, Order $order)
    {
        Gate::authorize('manageOrder', $operation);

        OperationService::updateOrder($operation, $order, $request->validated());

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Remove one order from the specified operation.
     */
    public function removeOrder(RemoveOperationOrderRequest $request, Operation $operation, Order $order)
    {
        Gate::authorize('manageOrder', $operation);

        OperationService::deleteOrder($operation, $order);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Update status for one order in the specified operation.
     */
    public function updateOrderStatus(UpdateOperationOrderStatusRequest $request, Operation $operation, Order $order)
    {
        Gate::authorize('manageOrder', $operation);

        OperationService::updateOrderStatus($operation, $order, $request->input('status'));

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Confirm production for the specified operation.
     */
    public function confirmProduction(Operation $operation)
    {
        Gate::authorize('manageProduction', $operation);

        OperationService::confirmProduction($operation);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Cancel production for the specified operation.
     */
    public function cancelProduction(Operation $operation)
    {
        Gate::authorize('manageProduction', $operation);

        OperationService::cancelProduction($operation);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Mark production as completed (Admin M&H). Allowed only from Confermata.
     */
    public function markProductionCompleted(Operation $operation)
    {
        Gate::authorize('manageProduction', $operation);

        OperationService::markProductionCompleted($operation);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Reopen production (Admin M&H). Allowed only from Completata → Confermata.
     */
    public function reopenProduction(Operation $operation)
    {
        Gate::authorize('manageProduction', $operation);

        OperationService::reopenProduction($operation);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Admin M&H marks the supplier case as Completata.
     */
    public function markCaseCompleted(Operation $operation, Request $request)
    {
        Gate::authorize('manageProduction', $operation);

        OperationService::markCaseCompletedByAdmin($operation, $request->user());

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Admin M&H reopens a completed supplier case.
     */
    public function reopenCase(Operation $operation, Request $request)
    {
        Gate::authorize('manageProduction', $operation);

        OperationService::reopenCaseByAdmin($operation, $request->user());

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Add an invoice to the specified operation.
     */
    public function addInvoice(AddOperationInvoiceRequest $request, Operation $operation)
    {
        Gate::authorize('manageInvoice', $operation);

        OperationService::createInvoice($operation, $request->validated());

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Update one invoice for the specified operation.
     */
    public function updateInvoice(UpdateOperationInvoiceRequest $request, Operation $operation, Invoice $invoice)
    {
        Gate::authorize('manageInvoice', $operation);

        OperationService::updateInvoice($operation, $invoice, $request->validated());

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Remove one invoice from the specified operation.
     */
    public function removeInvoice(RemoveOperationInvoiceRequest $request, Operation $operation, Invoice $invoice)
    {
        Gate::authorize('manageInvoice', $operation);

        OperationService::deleteInvoice($operation, $invoice);

        return to_route('operations.show', ['operation' => $operation->id]);
    }

    /**
     * Update status for one invoice in the specified operation.
     */
    public function updateInvoiceStatus(UpdateOperationInvoiceStatusRequest $request, Operation $operation, Invoice $invoice)
    {
        Gate::authorize('manageInvoice', $operation);

        OperationService::updateInvoiceStatus($operation, $invoice, $request->input('status'));

        return to_route('operations.show', ['operation' => $operation->id]);
    }
}