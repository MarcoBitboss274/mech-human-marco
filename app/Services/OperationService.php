<?php

namespace App\Services;

use App\Enums\InvoiceStatusEnum;
use App\Enums\OperationStatusEnum;
use App\Enums\OperationSupplierStatusEnum;
use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\ProductionStatusEnum;
use App\Enums\QuoteStatusEnum;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Operation;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Production;
use App\Models\Quote;
use App\Models\Supplier;
use App\Notifications\Admin\SendPrescriptionForAdmin;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * @method static void cancel(Operation $operation)
 * @method static void reactivate(Operation $operation)
 * @method static void archive(Operation $operation)
 * @method static void reopen(Operation $operation)
 */
class OperationService extends ModelService
{
    /**
     * Get the class of the model
     */
    protected static function getClass(): string
    {
        return Operation::class;
    }

    public static function cancel(Operation $operation): void
    {
        if ($operation->canceled_at) {
            return;
        }

        $operation->update([
            'canceled_at' => now(),
        ]);
    }

    public static function reactivate(Operation $operation): void
    {
        if (! $operation->canceled_at) {
            return;
        }

        $operation->update([
            'canceled_at' => null,
        ]);
    }

    public static function archive(Operation $operation): void
    {
        if ($operation->archived_at) {
            return;
        }

        $operation->update([
            'archived_at' => now(),
        ]);
    }

    public static function reopen(Operation $operation): void
    {
        if (! $operation->archived_at) {
            return;
        }

        $operation->update([
            'archived_at' => null,
        ]);
    }

    /**
     * Fetch model
     */
    public static function fetch(Request|null $request)
    {
        $query = static::getClass()::query()
            ->with('building:id,name')
            ->addSelect([
                'selected_supplier_name' => Supplier::query()
                    ->select('suppliers.name')
                    ->join('operation_supplier', 'operation_supplier.supplier_id', '=', 'suppliers.id')
                    ->whereColumn('operation_supplier.operation_id', 'operations.id')
                    ->where('operation_supplier.selected', true)
                    ->orderByDesc('operation_supplier.updated_at')
                    ->limit(1),
                'latest_quote_status' => Quote::query()
                    ->select('status')
                    ->whereColumn('quotes.operation_id', 'operations.id')
                    ->latest('created_at')
                    ->latest('id')
                    ->limit(1),
            ]);

        $user = $request?->user();
        if (! $user->can('operations.view-all')) {

            if ($user->isAgent()) {
                $query->whereIn('building_id', Building::query()
                    ->select('id')
                    ->where('agent_id', $user->id));
            } elseif ($user->isCustomer()) {
                $query->whereIn('building_id', $user->buildings()->pluck('buildings.id'));
            }
        }

        return $query;
    }

    /**
     * Apply search
     */
    public static function applySearch(Builder $query, Request|null $request): Builder
    {
        if ($request['query'] ?? false) {
            QueryService::querySearch($query, $request['query'], [
                'latestPrescription.ref',
                'batch_number'
            ]);
        }

        return $query;
    }

    /**
     * Apply filters
     */
    public static function applyFilters(Builder $query, Request|null $request): Builder
    {
        $currentUser = UserService::currentUser();

        $mode = $request?->input('mode');
        $modeValue = is_array($mode) ? ($mode[0] ?? null) : $mode;
        $modeValue = ($modeValue !== null && $modeValue !== '') ? (string) $modeValue : 'draft';

        if ($modeValue === 'draft') {
            $query
                ->where('status', OperationStatusEnum::DRAFT->value)
                ->whereNull('archived_at');
        } elseif ($modeValue === 'active') {
            $query
                ->whereIn('status', [
                    OperationStatusEnum::REQUESTED->value,
                    OperationStatusEnum::IN_PROGRESS->value,
                    OperationStatusEnum::WAITING_APPROVAL->value,
                    OperationStatusEnum::PRODUCTION->value,
                    OperationStatusEnum::COMPLETED->value,
                ])
                ->whereNull('archived_at');
        } elseif ($modeValue === 'archived') {
            $query->whereNotNull('archived_at');
        }

        $buildingId = $request?->input('building_id');
        if ($buildingId !== null && $buildingId !== '') {
            $id = (int) $buildingId;
            if ($id > 0) {
                $query->where('building_id', $id);
            }
        }

        $requesterId = $request?->input('requester_id');
        if ($requesterId !== null && $requesterId !== '') {
            $id = (int) $requesterId;
            if ($id > 0) {
                $query->whereHas('latestPrescription', function (Builder $p) use ($id) {
                    $p->where('user_id', $id);
                });
            }
        }

        $prescriptionTypology = $request?->input('prescription_typology');
        if ($prescriptionTypology !== null && $prescriptionTypology !== '') {
            $value = is_array($prescriptionTypology) ? ($prescriptionTypology[0] ?? null) : $prescriptionTypology;
            if ($value !== null && $value !== '') {
                $query->whereHas('latestPrescription', function (Builder $p) use ($value) {
                    $p->where('typology', $value);
                });
            }
        }

        $status = $request?->input('status');
        if ($status !== null && $status !== '') {
            $value = is_array($status) ? ($status[0] ?? null) : $status;
            if ($value !== null && $value !== '') {
                $query->where('status', $value);
            }
        }

        $latestQuoteStatus = $request?->input('latest_quote_status');
        if ($latestQuoteStatus !== null && $latestQuoteStatus !== '') {
            $value = is_array($latestQuoteStatus) ? ($latestQuoteStatus[0] ?? null) : $latestQuoteStatus;
            if ($value !== null && $value !== '') {
                $query->whereRaw(
                    '(select `status` from `quotes` where `quotes`.`operation_id` = `operations`.`id` order by `created_at` desc, `id` desc limit 1) = ?',
                    [$value],
                );
            }
        }

        if ($currentUser->can('operations.index.supplier')) {
            $supplierId = $request?->input('supplier_id');
            if ($supplierId !== null && $supplierId !== '') {
                $id = (int) $supplierId;
                if ($id > 0) {
                    $query->whereHas('selectedSupplier', function (Builder $s) use ($id) {
                        $s->where('suppliers.id', $id);
                    });
                }
            }
        }

        $expireAtFrom = $request?->input('expire_at_from');
        $expireAtTo = $request?->input('expire_at_to');
        if (($expireAtFrom !== null && $expireAtFrom !== '') || ($expireAtTo !== null && $expireAtTo !== '')) {
            $from = null;
            $to = null;

            if ($expireAtFrom !== null && $expireAtFrom !== '') {
                try {
                    $from = Carbon::parse((string) $expireAtFrom)->startOfDay();
                } catch (\Throwable $e) {
                    $from = null;
                }
            }

            if ($expireAtTo !== null && $expireAtTo !== '') {
                try {
                    $to = Carbon::parse((string) $expireAtTo)->endOfDay();
                } catch (\Throwable $e) {
                    $to = null;
                }
            }

            if ($from || $to) {
                $query->whereHas('latestPrescription', function (Builder $p) use ($from, $to) {
                    if ($from && $to) {
                        $p->whereBetween('expire_at', [$from, $to]);
                    } elseif ($from) {
                        $p->where('expire_at', '>=', $from);
                    } else {
                        $p->where('expire_at', '<=', $to);
                    }
                });
            }
        }

        $sendAtFrom = $request?->input('send_at_from');
        $sendAtTo = $request?->input('send_at_to');
        if (($sendAtFrom !== null && $sendAtFrom !== '') || ($sendAtTo !== null && $sendAtTo !== '')) {
            $from = null;
            $to = null;

            if ($sendAtFrom !== null && $sendAtFrom !== '') {
                try {
                    $from = Carbon::parse((string) $sendAtFrom)->startOfDay();
                } catch (\Throwable $e) {
                    $from = null;
                }
            }

            if ($sendAtTo !== null && $sendAtTo !== '') {
                try {
                    $to = Carbon::parse((string) $sendAtTo)->endOfDay();
                } catch (\Throwable $e) {
                    $to = null;
                }
            }

            if ($from || $to) {
                $query->whereHas('latestPrescription', function (Builder $p) use ($from, $to) {
                    if ($from && $to) {
                        $p->whereBetween('send_at', [$from, $to]);
                    } elseif ($from) {
                        $p->where('send_at', '>=', $from);
                    } else {
                        $p->where('send_at', '<=', $to);
                    }
                });
            }
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
     * Create operation with linked prescription (wizard flow).
     */
    public static function createWithPrescription(array $payload): Operation
    {
        return DB::transaction(function () use ($payload) {
            $isDraft = (bool) ($payload['draft'] ?? false);
            $sendAt = $isDraft ? null : now();
            $operationStatus = $isDraft
                ? OperationStatusEnum::DRAFT->value
                : OperationStatusEnum::REQUESTED->value;
            $prescriptionStatus = $isDraft
                ? PrescriptionStatusEnum::DRAFT->value
                : PrescriptionStatusEnum::SENT->value;

            $operation = static::store(null, [
                'building_id' => $payload['building_id'],
                'typology' => $payload['typology'],
                'status' => $operationStatus,
                'batch_number' => OperationService::createBatchNumber(),
            ]);

            $building = Building::query()->select(['id', 'name'])->find($payload['building_id']);

            $prescription = Prescription::query()->create([
                'operation_id' => $operation->id,
                'building_id' => $payload['building_id'],
                'user_id' => $payload['user_id'] ?? null,
                'typology' => $payload['typology'],
                'ref' => $payload['ref'] ?? null,
                'manual' => $payload['manual'] ?? null,
                'name' => $payload['name'] ?? null,
                'surname' => $payload['surname'] ?? null,
                'age' => $payload['age'] ?? null,
                'gender' => $payload['gender'] ?? null,
                'company_name' => $building?->name,
                'address' => $payload['address'] ?? null,
                'city' => $payload['city'] ?? null,
                'province' => $payload['province'] ?? null,
                'cap' => $payload['cap'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'status' => $prescriptionStatus,
                'send_at' => $sendAt,
                'expire_at' => $sendAt?->copy()->addMonths(6),
            ]);

            static::syncPrescriptionDetails($prescription, $payload);

            if ($prescriptionStatus === PrescriptionStatusEnum::SENT->value) {
                NotificationService::sendToAdmins(new SendPrescriptionForAdmin($prescription));
            }

            return $operation;
        });
    }

    /**
     * Update operation with latest linked prescription (wizard flow).
     */
    public static function updateWithPrescription(Operation $operation, array $payload): void
    {
        DB::transaction(function () use ($operation, $payload) {
            $isDraft = (bool) ($payload['draft'] ?? false);
            $sendAt = $isDraft ? null : now();
            $operationStatus = $isDraft
                ? OperationStatusEnum::DRAFT->value
                : OperationStatusEnum::REQUESTED->value;
            $prescriptionStatus = $isDraft
                ? PrescriptionStatusEnum::DRAFT->value
                : PrescriptionStatusEnum::SENT->value;

            $latestPrescription = $operation->latestPrescription()->first();

            if (! $latestPrescription) {
                throw ValidationException::withMessages([
                    'operation' => 'The selected operation has no linked prescription.',
                ]);
            }

            if ($latestPrescription->status !== PrescriptionStatusEnum::DRAFT->value) {
                throw ValidationException::withMessages([
                    'operation' => 'Only draft prescriptions can be edited from the wizard.',
                ]);
            }

            $operation->fill([
                'building_id' => $payload['building_id'],
                'typology' => $payload['typology'],
                'status' => $operationStatus,
            ]);
            $operation->save();

            $building = Building::query()->select(['id', 'name'])->find($payload['building_id']);

            $latestPrescription->fill([
                'building_id' => $payload['building_id'],
                'user_id' => $payload['user_id'] ?? null,
                'typology' => $payload['typology'],
                'ref' => $payload['ref'] ?? null,
                'manual' => $payload['manual'] ?? null,
                'name' => $payload['name'] ?? null,
                'surname' => $payload['surname'] ?? null,
                'age' => $payload['age'] ?? null,
                'gender' => $payload['gender'] ?? null,
                'company_name' => $building?->name,
                'address' => $payload['address'] ?? null,
                'city' => $payload['city'] ?? null,
                'province' => $payload['province'] ?? null,
                'cap' => $payload['cap'] ?? null,
                'notes' => $payload['notes'] ?? null,
                'status' => $prescriptionStatus,
                'send_at' => $sendAt,
                'expire_at' => $sendAt?->copy()->addMonths(6),
            ]);
            $latestPrescription->save();

            static::syncPrescriptionDetails($latestPrescription, $payload);

            if ($prescriptionStatus === PrescriptionStatusEnum::SENT->value) {
                NotificationService::sendToAdmins(new SendPrescriptionForAdmin($latestPrescription));
            }
        });
    }

    /**
     * Build edit wizard payload for one operation.
     */
    public static function getEditWizardData(Operation $operation): array
    {
        $operation->load([
            'latestPrescription.user:id,name,surname',
            'latestPrescription.building:id,name',
            'latestPrescription.protrusorDetails',
            'latestPrescription.lybraAlignerDetails',
            'latestPrescription.guidedSurgeryDetails',
            'latestPrescription.threeDMeshDetails',
            'latestPrescription.prosthesisDetails',
            'latestPrescription.semiFinishedProsthesisDetails',
        ]);

        $latestPrescription = $operation->latestPrescription;

        if (! $latestPrescription) {
            throw ValidationException::withMessages([
                'operation' => 'The selected operation has no linked prescription.',
            ]);
        }

        if ($latestPrescription->status !== PrescriptionStatusEnum::DRAFT->value) {
            throw ValidationException::withMessages([
                'operation' => 'Only draft prescriptions can be edited from the wizard.',
            ]);
        }

        $form = static::defaultWizardFormPayload();
        $form['draft'] = false;
        $form['building_id'] = $operation->building_id;
        $form['user_id'] = $latestPrescription->user_id;
        $form['typology'] = $latestPrescription->typology;
        $form['ref'] = $latestPrescription->ref;
        $form['manual'] = $latestPrescription->manual;
        $form['name'] = $latestPrescription->name;
        $form['surname'] = $latestPrescription->surname;
        $form['age'] = static::stringifyNullable($latestPrescription->age);
        $form['gender'] = $latestPrescription->gender;
        $form['company_name'] = $latestPrescription->company_name;
        $form['address'] = $latestPrescription->address;
        $form['city'] = $latestPrescription->city;
        $form['province'] = $latestPrescription->province;
        $form['cap'] = $latestPrescription->cap;
        $form['notes'] = $latestPrescription->notes;
        $form['protrusor_details'] = [
            'protrusor_typology' => $latestPrescription->protrusorDetails?->protrusor_typology,
            'odontogram' => static::serializeJsonArray($latestPrescription->protrusorDetails?->odontogram),
            'jig' => $latestPrescription->protrusorDetails?->jig,
            'remaining_upper_teeth' => $latestPrescription->protrusorDetails?->remaining_upper_teeth,
            'remaining_lower_teeth' => $latestPrescription->protrusorDetails?->remaining_lower_teeth,
            'transpalatal_arch' => $latestPrescription->protrusorDetails?->transpalatal_arch,
            'mandibular_advancement' => static::stringifyNullable($latestPrescription->protrusorDetails?->mandibular_advancement),
            'mandibular_advancement_2' => static::stringifyNullable($latestPrescription->protrusorDetails?->mandibular_advancement_2),
            'note' => $latestPrescription->protrusorDetails?->note,
        ];
        $form['lybra_aligner_details'] = [
            'cut_line' => $latestPrescription->lybraAlignerDetails?->cut_line,
            'note' => $latestPrescription->lybraAlignerDetails?->note,
        ];
        $form['guided_surgery_details'] = [
            'surgery_typology' => $latestPrescription->guidedSurgeryDetails?->surgery_typology,
            'odontogram' => static::serializeJsonArray($latestPrescription->guidedSurgeryDetails?->odontogram),
            'desired_implant_line' => $latestPrescription->guidedSurgeryDetails?->desired_implant_line,
            'additional_info' => $latestPrescription->guidedSurgeryDetails?->additional_info,
            'note' => $latestPrescription->guidedSurgeryDetails?->note,
        ];
        $form['three_d_mesh_details'] = [
            'dimension' => $latestPrescription->threeDMeshDetails?->dimension,
            'odontogram' => static::serializeJsonArray($latestPrescription->threeDMeshDetails?->odontogram),
            'outer_finish' => $latestPrescription->threeDMeshDetails?->outer_finish,
            'inner_finish' => $latestPrescription->threeDMeshDetails?->inner_finish,
            'pattern' => $latestPrescription->threeDMeshDetails?->pattern,
            'stress_breakers' => $latestPrescription->threeDMeshDetails?->stress_breakers,
            '3d_model' => $latestPrescription->threeDMeshDetails?->{'3d_model'},
            'screw_diameter' => static::stringifyNullable($latestPrescription->threeDMeshDetails?->screw_diameter),
            'shared_project_note' => $latestPrescription->threeDMeshDetails?->shared_project_note,
            'note' => $latestPrescription->threeDMeshDetails?->note,
        ];
        $form['prosthesis_details'] = [
            'typology' => $latestPrescription->prosthesisDetails?->typology,
            'crowns_and_bridges_details' => $latestPrescription->prosthesisDetails?->crowns_and_bridges_details,
            'full_bridge_details' => $latestPrescription->prosthesisDetails?->full_bridge_details,
            'odontogram' => static::serializeJsonArray($latestPrescription->prosthesisDetails?->odontogram),
            '3d_normal_model' => $latestPrescription->prosthesisDetails?->{'3d_normal_model'},
            '3d_excellent_model' => $latestPrescription->prosthesisDetails?->{'3d_excellent_model'},
            'note' => $latestPrescription->prosthesisDetails?->note,
        ];
        $form['semi_finished_prostheses_details'] = [
            'typology' => $latestPrescription->semiFinishedProsthesisDetails?->typology,
            'crowns_and_bridges_details' => $latestPrescription->semiFinishedProsthesisDetails?->crowns_and_bridges_details,
            'full_bridge_details' => $latestPrescription->semiFinishedProsthesisDetails?->full_bridge_details,
            'odontogram' => static::serializeJsonArray($latestPrescription->semiFinishedProsthesisDetails?->odontogram),
            'note' => $latestPrescription->semiFinishedProsthesisDetails?->note,
        ];

        return [
            'mode' => 'edit',
            'operationId' => $operation->id,
            'prescriptionId' => $latestPrescription->id,
            'initialForm' => $form,
            'buildings' => static::getWizardBuildingsPayload(),
        ];
    }

    /**
     * Get payload for admin operation show page
     */
    public static function getAdminShowData(Operation $operation): array
    {
        $operation->load([
            'building:id,name',
            'prescriptions' => fn($q) => $q->latest()->with([
                'user:id,name,surname',
                'building:id,name',
                'protrusorDetails',
                'lybraAlignerDetails',
                'guidedSurgeryDetails',
                'threeDMeshDetails',
                'prosthesisDetails',
                'semiFinishedProsthesisDetails',
            ]),
            'quotes' => fn($q) => $q->latest(),
            'orders' => fn($q) => $q->latest(),
            'productions' => fn($q) => $q->oldest(),
            'invoices' => fn($q) => $q->latest()->with(['media']),
            'suppliers:id,name,vat,mail,phone,address,cap,city,province,status',
            'selectedSupplier:id,name,vat,mail,phone,address,cap,city,province,status',
            'latestPrescription',
        ]);

        $selectedSupplier = $operation->selectedSupplier->first();
        $operationData = $operation->toArray();
        $operationData['selected_supplier'] = $selectedSupplier?->toArray();

        return [
            'operation' => $operationData,
        ];
    }

    /**
     * Get payload for agent operation show page
     */
    public static function getAgentShowData(Operation $operation): array
    {
        $operation->load([
            'building:id,name',
            'prescriptions' => fn($q) => $q->select(['id', 'operation_id'])->latest(),
            'quotes' => fn($q) => $q->select(['id', 'operation_id', 'status'])->latest(),
            'orders' => fn($q) => $q->select(['id', 'operation_id', 'status'])->latest(),
            'productions' => fn($q) => $q->select(['id', 'operation_id', 'status', 'confirmed_at', 'canceled_at'])->oldest(),
            'invoices' => fn($q) => $q->select(['id', 'operation_id', 'status'])->latest(),
            'latestPrescription:id,operation_id,user_id,typology,ref,created_at',
        ]);

        $operationData = $operation->toArray();

        return [
            'operation' => $operationData,
        ];
    }

    /**
     * Confirm production for the operation.
     */
    public static function confirmProduction(Operation $operation): void
    {
        DB::transaction(function () use ($operation) {
            $production = $operation->productions()->oldest()->first();

            if (! $production) {
                Production::query()->create([
                    'operation_id' => $operation->id,
                    'status' => ProductionStatusEnum::CONFIRMED->value,
                ]);
            } elseif ($production->status !== ProductionStatusEnum::CONFIRMED->value) {
                $production->update([
                    'status' => ProductionStatusEnum::CONFIRMED->value,
                ]);
            }

            static::updateStatus($operation, OperationStatusEnum::PRODUCTION->value, true);
        });
    }

    /**
     * Cancel production for the operation.
     */
    public static function cancelProduction(Operation $operation): void
    {
        $production = $operation->productions()->oldest()->first();

        if (! $production || $production->status === ProductionStatusEnum::CANCELED->value) {
            return;
        }

        $production->update([
            'status' => ProductionStatusEnum::CANCELED->value,
        ]);
    }

    /**
     * Update the operation status.
     */
    public static function updateStatus(Operation $operation, string $status, ?bool $force = false): void
    {
        if ($force) {
            $operation->update([
                'status' => $status,
            ]);
        } else {
            if (OperationStatusEnum::from($operation->status)->sortOrder() < OperationStatusEnum::from($status)->sortOrder()) {
                $operation->update([
                    'status' => $status,
                ]);
            }
        }

        return;
    }

    /**
     * Attach a supplier to the operation.
     */
    public static function attachSupplier(Operation $operation, Supplier $supplier): void
    {
        $alreadyAttached = $operation->suppliers()
            ->where('suppliers.id', $supplier->id)
            ->exists();

        if ($alreadyAttached) {
            return;
        }

        $operation->suppliers()->attach($supplier->id, [
            'status' => OperationSupplierStatusEnum::TO_CONTACT->value,
            'selected' => false,
        ]);
    }

    /**
     * Select a supplier for the operation.
     */
    public static function selectSupplier(Operation $operation, Supplier $supplier): void
    {
        $isAttached = $operation->suppliers()
            ->where('suppliers.id', $supplier->id)
            ->exists();

        if (! $isAttached) {
            throw ValidationException::withMessages([
                'supplier_id' => 'The supplier is not associated with this operation.',
            ]);
        }

        DB::transaction(function () use ($operation, $supplier) {
            $operation->suppliers()
                ->newPivotStatement()
                ->where('operation_id', $operation->id)
                ->update([
                    'selected' => false,
                    'updated_at' => now(),
                ]);

            $operation->suppliers()->updateExistingPivot($supplier->id, [
                'selected' => true,
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Detach a supplier from the operation.
     */
    public static function detachSupplier(Operation $operation, Supplier $supplier): void
    {
        $operation->suppliers()->detach($supplier->id);
    }

    /**
     * Update the supplier status on operation pivot.
     */
    public static function updateSupplierStatus(Operation $operation, Supplier $supplier, ?string $status): void
    {
        $operation->suppliers()->updateExistingPivot($supplier->id, [
            'status' => $status,
            'updated_at' => now(),
        ]);
    }

    /**
     * Update one prescription attached to the operation.
     */
    public static function updatePrescription(Operation $operation, Prescription $prescription, array $payload): void
    {
        if ($prescription->operation_id !== $operation->id) {
            throw ValidationException::withMessages([
                'prescription' => 'The selected prescription is not associated with this operation.',
            ]);
        }

        $prescription->fill([
            'user_id' => $payload['user_id'] ?? null,
            'typology' => $payload['typology'] ?? null,
            'ref' => $payload['ref'] ?? null,
            'name' => $payload['name'] ?? null,
            'surname' => $payload['surname'] ?? null,
            'age' => $payload['age'] ?? null,
            'gender' => $payload['gender'] ?? null,
        ]);
        $prescription->save();

        static::syncPrescriptionDetails($prescription, $payload);
    }

    /**
     * Sync typology details attached to one prescription.
     */
    private static function syncPrescriptionDetails(Prescription $prescription, array $payload): void
    {
        $effectiveTypology = $payload['typology'] ?? $prescription->typology;
        $isManual = (bool) ($payload['manual'] ?? $prescription->manual ?? false);

        if ($effectiveTypology === PrescriptionTypologyEnum::PROTRUSOR->value) {
            $prescription->protrusorDetails()->updateOrCreate(
                ['prescription_id' => $prescription->id],
                [
                    'protrusor_typology' => data_get($payload, 'protrusor_details.protrusor_typology'),
                    'odontogram' => static::normalizeJsonArray(data_get($payload, 'protrusor_details.odontogram')),
                    'jig' => data_get($payload, 'protrusor_details.jig'),
                    'remaining_upper_teeth' => data_get($payload, 'protrusor_details.remaining_upper_teeth'),
                    'remaining_lower_teeth' => data_get($payload, 'protrusor_details.remaining_lower_teeth'),
                    'transpalatal_arch' => data_get($payload, 'protrusor_details.transpalatal_arch'),
                    'mandibular_advancement' => data_get($payload, 'protrusor_details.mandibular_advancement'),
                    'mandibular_advancement_2' => data_get($payload, 'protrusor_details.mandibular_advancement_2'),
                    'note' => data_get($payload, 'protrusor_details.note'),
                ],
            );
            $details = $prescription->protrusorDetails()->first();
            if ($details) {
                if ($isManual) {
                    static::clearDetailsMediaCollections($details, [
                        'scansione_intraorale',
                        'rilevazione_dell_avanzamento_mandibolare_con_occlusione',
                    ]);
                } else {
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'protrusor_attachments.scansione_intraorale'),
                        'scansione_intraorale',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'protrusor_attachments.rilevazione_dell_avanzamento_mandibolare_con_occlusione'),
                        'rilevazione_dell_avanzamento_mandibolare_con_occlusione',
                    );
                }
            }
            $prescription->lybraAlignerDetails()->delete();
            $prescription->guidedSurgeryDetails()->delete();
            $prescription->threeDMeshDetails()->delete();
            $prescription->prosthesisDetails()->delete();
            $prescription->semiFinishedProsthesisDetails()->delete();

            return;
        }

        if ($effectiveTypology === PrescriptionTypologyEnum::LYBRA_ALIGNER->value) {
            $prescription->lybraAlignerDetails()->updateOrCreate(
                ['prescription_id' => $prescription->id],
                [
                    'cut_line' => data_get($payload, 'lybra_aligner_details.cut_line'),
                    'note' => data_get($payload, 'lybra_aligner_details.note'),
                ],
            );
            $details = $prescription->lybraAlignerDetails()->first();
            if ($details) {
                if ($isManual) {
                    static::clearDetailsMediaCollections($details, [
                        'scansione_intraorale',
                        'foto_del_sorriso_e_morso_del_paziente',
                        'ortopantomografia',
                        'rx_anteroposteriore_delle_ossa_mascellari',
                    ]);
                } else {
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'lybra_aligner_attachments.scansione_intraorale'),
                        'scansione_intraorale',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'lybra_aligner_attachments.foto_del_sorriso_e_morso_del_paziente'),
                        'foto_del_sorriso_e_morso_del_paziente',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'lybra_aligner_attachments.ortopantomografia'),
                        'ortopantomografia',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'lybra_aligner_attachments.rx_anteroposteriore_delle_ossa_mascellari'),
                        'rx_anteroposteriore_delle_ossa_mascellari',
                    );
                }
            }
            $prescription->protrusorDetails()->delete();
            $prescription->guidedSurgeryDetails()->delete();
            $prescription->threeDMeshDetails()->delete();
            $prescription->prosthesisDetails()->delete();
            $prescription->semiFinishedProsthesisDetails()->delete();

            return;
        }

        if ($effectiveTypology === PrescriptionTypologyEnum::GUIDED_SURGERY->value) {
            $prescription->guidedSurgeryDetails()->updateOrCreate(
                ['prescription_id' => $prescription->id],
                [
                    'surgery_typology' => data_get($payload, 'guided_surgery_details.surgery_typology'),
                    'odontogram' => static::normalizeJsonArray(data_get($payload, 'guided_surgery_details.odontogram')),
                    'desired_implant_line' => data_get($payload, 'guided_surgery_details.desired_implant_line'),
                    'additional_info' => data_get($payload, 'guided_surgery_details.additional_info'),
                    'note' => data_get($payload, 'guided_surgery_details.note'),
                ],
            );
            $details = $prescription->guidedSurgeryDetails()->first();
            if ($details) {
                if ($isManual) {
                    static::clearDetailsMediaCollections($details, [
                        'scansione_intraorale',
                        'cbct_allineabile_con_la_scansione_rilevata',
                        'ceratura_diagnostica',
                    ]);
                } else {
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'guided_surgery_attachments.scansione_intraorale'),
                        'scansione_intraorale',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'guided_surgery_attachments.cbct_allineabile_con_la_scansione_rilevata'),
                        'cbct_allineabile_con_la_scansione_rilevata',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'guided_surgery_attachments.ceratura_diagnostica'),
                        'ceratura_diagnostica',
                    );
                }
            }
            $prescription->protrusorDetails()->delete();
            $prescription->lybraAlignerDetails()->delete();
            $prescription->threeDMeshDetails()->delete();
            $prescription->prosthesisDetails()->delete();
            $prescription->semiFinishedProsthesisDetails()->delete();

            return;
        }

        if ($effectiveTypology === PrescriptionTypologyEnum::THREE_D_MESH->value) {
            $prescription->threeDMeshDetails()->updateOrCreate(
                ['prescription_id' => $prescription->id],
                [
                    'dimension' => data_get($payload, 'three_d_mesh_details.dimension'),
                    'odontogram' => static::normalizeJsonArray(data_get($payload, 'three_d_mesh_details.odontogram')),
                    'outer_finish' => data_get($payload, 'three_d_mesh_details.outer_finish'),
                    'inner_finish' => data_get($payload, 'three_d_mesh_details.inner_finish'),
                    'pattern' => data_get($payload, 'three_d_mesh_details.pattern'),
                    'stress_breakers' => data_get($payload, 'three_d_mesh_details.stress_breakers'),
                    '3d_model' => data_get($payload, 'three_d_mesh_details.3d_model'),
                    'screw_diameter' => data_get($payload, 'three_d_mesh_details.screw_diameter'),
                    'shared_project_note' => data_get($payload, 'three_d_mesh_details.shared_project_note'),
                    'note' => data_get($payload, 'three_d_mesh_details.note'),
                ],
            );
            $details = $prescription->threeDMeshDetails()->first();
            if ($details) {
                if ($isManual) {
                    static::clearDetailsMediaCollections($details, [
                        'scansione_intraorale',
                        'cbct_allineabile_con_la_scansione_rilevata',
                        'ceratura_diagnostica',
                        'scansione_facciale_o_foto_del_sorriso',
                    ]);
                } else {
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'three_d_mesh_attachments.scansione_intraorale'),
                        'scansione_intraorale',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'three_d_mesh_attachments.cbct_allineabile_con_la_scansione_rilevata'),
                        'cbct_allineabile_con_la_scansione_rilevata',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'three_d_mesh_attachments.ceratura_diagnostica'),
                        'ceratura_diagnostica',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'three_d_mesh_attachments.scansione_facciale_o_foto_del_sorriso'),
                        'scansione_facciale_o_foto_del_sorriso',
                    );
                }
            }
            $prescription->protrusorDetails()->delete();
            $prescription->lybraAlignerDetails()->delete();
            $prescription->guidedSurgeryDetails()->delete();
            $prescription->prosthesisDetails()->delete();
            $prescription->semiFinishedProsthesisDetails()->delete();

            return;
        }

        if ($effectiveTypology === PrescriptionTypologyEnum::PROSTHESIS->value) {
            $prescription->prosthesisDetails()->updateOrCreate(
                ['prescription_id' => $prescription->id],
                [
                    'typology' => data_get($payload, 'prosthesis_details.typology'),
                    'crowns_and_bridges_details' => data_get($payload, 'prosthesis_details.crowns_and_bridges_details'),
                    'full_bridge_details' => data_get($payload, 'prosthesis_details.full_bridge_details'),
                    'odontogram' => static::normalizeJsonArray(data_get($payload, 'prosthesis_details.odontogram')),
                    '3d_normal_model' => data_get($payload, 'prosthesis_details.3d_normal_model'),
                    '3d_excellent_model' => data_get($payload, 'prosthesis_details.3d_excellent_model'),
                    'note' => data_get($payload, 'prosthesis_details.note'),
                ],
            );
            $details = $prescription->prosthesisDetails()->first();
            if ($details) {
                if ($isManual) {
                    static::clearDetailsMediaCollections($details, [
                        'scansione_intraorale',
                        'articolazione',
                        'foto_con_campione_colore',
                        'foto_del_sorriso',
                        'scansione_e_foto_del_provvisorio',
                    ]);
                } else {
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'prosthesis_attachments.scansione_intraorale'),
                        'scansione_intraorale',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'prosthesis_attachments.articolazione'),
                        'articolazione',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'prosthesis_attachments.foto_con_campione_colore'),
                        'foto_con_campione_colore',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'prosthesis_attachments.foto_del_sorriso'),
                        'foto_del_sorriso',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'prosthesis_attachments.scansione_e_foto_del_provvisorio'),
                        'scansione_e_foto_del_provvisorio',
                    );
                }
            }
            $prescription->protrusorDetails()->delete();
            $prescription->lybraAlignerDetails()->delete();
            $prescription->guidedSurgeryDetails()->delete();
            $prescription->threeDMeshDetails()->delete();
            $prescription->semiFinishedProsthesisDetails()->delete();

            return;
        }

        if ($effectiveTypology === PrescriptionTypologyEnum::SEMI_FINISHED_PROSTHESES->value) {
            $prescription->semiFinishedProsthesisDetails()->updateOrCreate(
                ['prescription_id' => $prescription->id],
                [
                    'typology' => data_get($payload, 'semi_finished_prostheses_details.typology'),
                    'crowns_and_bridges_details' => data_get($payload, 'semi_finished_prostheses_details.crowns_and_bridges_details'),
                    'full_bridge_details' => data_get($payload, 'semi_finished_prostheses_details.full_bridge_details'),
                    'odontogram' => static::normalizeJsonArray(data_get($payload, 'semi_finished_prostheses_details.odontogram')),
                    'note' => data_get($payload, 'semi_finished_prostheses_details.note'),
                ],
            );
            $details = $prescription->semiFinishedProsthesisDetails()->first();
            if ($details) {
                if ($isManual) {
                    static::clearDetailsMediaCollections($details, [
                        'progetto_in_stl_da_fresare_oppure_scansione_digitale_completa',
                        'scansione_del_provvisorio',
                    ]);
                } else {
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'semi_finished_prostheses_attachments.progetto_in_stl_da_fresare_oppure_scansione_digitale_completa'),
                        'progetto_in_stl_da_fresare_oppure_scansione_digitale_completa',
                    );
                    static::syncSingleDetailsMedia(
                        $details,
                        data_get($payload, 'semi_finished_prostheses_attachments.scansione_del_provvisorio'),
                        'scansione_del_provvisorio',
                    );
                }
            }
            $prescription->protrusorDetails()->delete();
            $prescription->lybraAlignerDetails()->delete();
            $prescription->guidedSurgeryDetails()->delete();
            $prescription->threeDMeshDetails()->delete();
            $prescription->prosthesisDetails()->delete();

            return;
        }

        $prescription->protrusorDetails()->delete();
        $prescription->lybraAlignerDetails()->delete();
        $prescription->guidedSurgeryDetails()->delete();
        $prescription->threeDMeshDetails()->delete();
        $prescription->prosthesisDetails()->delete();
        $prescription->semiFinishedProsthesisDetails()->delete();
    }

    /**
     * @param object $details
     */
    private static function clearDetailsMediaCollections(object $details, array $collections): void
    {
        foreach ($collections as $collection) {
            if (method_exists($details, 'clearMediaCollection')) {
                $details->clearMediaCollection($collection);
            }
        }
    }

    /**
     * @param object $details
     */
    private static function syncSingleDetailsMedia(object $details, mixed $file, string $collection): void
    {
        if (! $file instanceof UploadedFile) {
            return;
        }

        if (! method_exists($details, 'clearMediaCollection') || ! method_exists($details, 'addMedia')) {
            return;
        }

        $details->clearMediaCollection($collection);
        $details->addMedia($file)->toMediaCollection($collection);
    }

    /**
     * Normalize nullable JSON payloads into array data.
     */
    private static function normalizeJsonArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            $normalized = array_values(array_filter($value, fn($item) => $item !== null && $item !== ''));
            $normalized = array_map(static function (mixed $item): int {
                if (is_int($item)) {
                    return $item;
                }

                if (is_numeric($item)) {
                    return (int) $item;
                }

                return 0;
            }, $normalized);

            $normalized = array_values(array_unique(array_filter($normalized, fn(int $item) => $item !== 0)));
            sort($normalized);

            return $normalized;
        }

        if (! is_string($value)) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? static::normalizeJsonArray($decoded) : null;
    }

    /**
     * Serialize JSON array payloads for wizard fields.
     */
    private static function serializeJsonArray(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            if (is_string($value)) {
                $decoded = json_decode($value, true);

                return is_array($decoded) ? static::normalizeJsonArray($decoded) ?? [] : [];
            }

            return [];
        }

        return static::normalizeJsonArray($value) ?? [];
    }

    /**
     * Get one empty default wizard form payload.
     */
    private static function defaultWizardFormPayload(): array
    {
        return [
            'draft' => false,
            'building_id' => null,
            'user_id' => null,
            'typology' => null,
            'ref' => null,
            'manual' => null,
            'name' => null,
            'surname' => null,
            'age' => null,
            'gender' => null,
            'company_name' => null,
            'address' => null,
            'city' => null,
            'province' => null,
            'cap' => null,
            'notes' => null,
            'protrusor_details' => [
                'protrusor_typology' => null,
                'odontogram' => [],
                'jig' => null,
                'remaining_upper_teeth' => null,
                'remaining_lower_teeth' => null,
                'transpalatal_arch' => null,
                'mandibular_advancement' => null,
                'mandibular_advancement_2' => null,
                'note' => null,
            ],
            'lybra_aligner_details' => [
                'cut_line' => null,
                'note' => null,
            ],
            'guided_surgery_details' => [
                'surgery_typology' => null,
                'odontogram' => [],
                'desired_implant_line' => null,
                'additional_info' => null,
                'note' => null,
            ],
            'three_d_mesh_details' => [
                'dimension' => null,
                'odontogram' => [],
                'outer_finish' => null,
                'inner_finish' => null,
                'pattern' => null,
                'stress_breakers' => null,
                '3d_model' => null,
                'screw_diameter' => null,
                'shared_project_note' => null,
                'note' => null,
            ],
            'prosthesis_details' => [
                'typology' => null,
                'crowns_and_bridges_details' => null,
                'full_bridge_details' => null,
                'odontogram' => [],
                '3d_normal_model' => null,
                '3d_excellent_model' => null,
                'note' => null,
            ],
            'semi_finished_prostheses_details' => [
                'typology' => null,
                'crowns_and_bridges_details' => null,
                'full_bridge_details' => null,
                'odontogram' => [],
                'note' => null,
            ],
        ];
    }

    /**
     * Get buildings payload used by operation wizard.
     */
    public static function getWizardBuildingsPayload(): array
    {
        return Building::query()
            ->select(['id', 'name', 'vat', 'legal_address'])
            ->with([
                'addresses' => fn($query) => $query
                    ->select(['id', 'building_id', 'street', 'cap', 'city', 'province', 'country', 'is_default'])
                    ->orderByDesc('is_default')
                    ->orderBy('id'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (Building $building): array {
                return [
                    'id' => $building->id,
                    'name' => $building->name,
                    'vat' => $building->vat,
                    'legal_address' => $building->legal_address,
                    'addresses' => $building->addresses
                        ->map(fn($address): array => [
                            'id' => $address->id,
                            'street' => $address->street,
                            'cap' => $address->cap,
                            'city' => $address->city,
                            'province' => $address->province,
                            'country' => $address->country,
                            'is_default' => (bool) $address->is_default,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Convert scalar values to string while preserving null.
     */
    private static function stringifyNullable(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Create one quote for the operation.
     */
    public static function createQuote(Operation $operation, array $payload): Quote
    {
        return Quote::query()->create(QuoteService::normalizeStatusPayload([
            'operation_id' => $operation->id,
            'status' => QuoteStatusEnum::DRAFT->value,
            'notes' => $payload['notes'] ?? null,
        ]));
    }

    /**
     * Update one quote attached to the operation.
     */
    public static function updateQuote(Operation $operation, Quote $quote, array $payload): void
    {
        if ($quote->operation_id !== $operation->id) {
            throw ValidationException::withMessages([
                'quote' => 'The selected quote is not associated with this operation.',
            ]);
        }

        $quote->fill(QuoteService::normalizeStatusPayload([
            'status' => $quote->status,
            'notes' => $payload['notes'] ?? null,
        ]));
        $quote->save();
    }

    /**
     * Delete one quote attached to the operation.
     */
    public static function deleteQuote(Operation $operation, Quote $quote): void
    {
        if ($quote->operation_id !== $operation->id) {
            throw ValidationException::withMessages([
                'quote' => 'The selected quote is not associated with this operation.',
            ]);
        }

        $quote->operation?->update([
            'status' => OperationStatusEnum::IN_PROGRESS->value,
        ]);

        $quote->delete();
    }

    /**
     * Update the quote status with accepted_at transition.
     */
    public static function updateQuoteStatus(Operation $operation, Quote $quote, string $status): void
    {
        if ($quote->operation_id !== $operation->id) {
            throw ValidationException::withMessages([
                'quote' => 'The selected quote is not associated with this operation.',
            ]);
        }

        $quote->fill(QuoteService::normalizeStatusPayload([
            'status' => $status,
            'notes' => $quote->notes,
        ]));
        $quote->save();
    }

    /**
     * Create one order for the operation.
     */
    public static function createOrder(Operation $operation, array $payload): void
    {
        OrderService::save(null, [
            'operation_id' => $operation->id,
            'amount' => $payload['amount'],
            'description' => $payload['description'],
        ]);
    }

    /**
     * Update one order attached to the operation.
     */
    public static function updateOrder(Operation $operation, Order $order, array $payload): void
    {
        if ($order->operation_id !== $operation->id) {
            throw ValidationException::withMessages([
                'order' => 'The selected order is not associated with this operation.',
            ]);
        }

        $order->fill([
            'amount' => $payload['amount'] ?? $order->amount,
            'description' => $payload['description'] ?? $order->description,
            'status' => $payload['status'] ?? $order->status,
        ]);
        $order->save();
    }

    /**
     * Delete one order attached to the operation.
     */
    public static function deleteOrder(Operation $operation, Order $order): void
    {
        if ($order->operation_id !== $operation->id) {
            throw ValidationException::withMessages([
                'order' => 'The selected order is not associated with this operation.',
            ]);
        }

        $order->delete();
    }

    /**
     * Update the order status.
     */
    public static function updateOrderStatus(Operation $operation, Order $order, string $status): void
    {
        if ($order->operation_id !== $operation->id) {
            throw ValidationException::withMessages([
                'order' => 'The selected order is not associated with this operation.',
            ]);
        }

        $order->fill([
            'status' => $status,
        ]);
        $order->save();
    }

    /**
     * Create one invoice for the operation.
     */
    public static function createInvoice(Operation $operation, array $payload): void
    {
        /** @var Invoice $invoice */
        $invoice = InvoiceService::store(null, [
            'operation_id' => $operation->id,
            'status' => InvoiceStatusEnum::DRAFT->value,
            'description' => $payload['description'],
        ]);

        $file = $payload['file'] ?? null;
        if ($file instanceof UploadedFile) {
            $invoice->addMedia($file)->toMediaCollection('invoice_file');
        }
    }

    /**
     * Update one invoice attached to the operation.
     */
    public static function updateInvoice(Operation $operation, Invoice $invoice, array $payload): void
    {
        if ($invoice->operation_id !== $operation->id) {
            throw ValidationException::withMessages([
                'invoice' => 'The selected invoice is not associated with this operation.',
            ]);
        }

        $invoice->fill([
            'description' => $payload['description'],
        ]);
        $invoice->save();

        $file = $payload['file'] ?? null;
        if ($file instanceof UploadedFile) {
            $invoice->addMedia($file)->toMediaCollection('invoice_file');
        }
    }

    /**
     * Delete one invoice attached to the operation.
     */
    public static function deleteInvoice(Operation $operation, Invoice $invoice): void
    {
        if ($invoice->operation_id !== $operation->id) {
            throw ValidationException::withMessages([
                'invoice' => 'The selected invoice is not associated with this operation.',
            ]);
        }

        $invoice->delete();
    }

    /**
     * Update the invoice status.
     */
    public static function updateInvoiceStatus(Operation $operation, Invoice $invoice, string $status): void
    {
        if ($invoice->operation_id !== $operation->id) {
            throw ValidationException::withMessages([
                'invoice' => 'The selected invoice is not associated with this operation.',
            ]);
        }

        $invoice->fill([
            'status' => $status,
        ]);
        $invoice->save();
    }

    /**
     * Create a unique batch number.
     */
    public static function createBatchNumber(): string
    {
        do {
            $letters = chr(random_int(65, 90)) . chr(random_int(65, 90));
            $numbers = str_pad((string) random_int(0, 99999), 5, '0', STR_PAD_LEFT);
            $candidate = $letters . $numbers;
        } while (Operation::query()->where('batch_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Check if the operation includes any of the given typologies.
     */
    public static function includeTypologies(Operation $operation, array $typologies): bool
    {
        $latestPrescription = $operation->latestPrescription()->first();

        if (! $latestPrescription) {
            return false;
        }

        return in_array($latestPrescription->typology, array_map(
            fn(PrescriptionTypologyEnum $typology) => $typology->value,
            $typologies,
        ), true);
    }
}