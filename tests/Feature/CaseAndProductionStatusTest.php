<?php

use App\Enums\CaseStatusEnum;
use App\Enums\OperationStatusEnum;
use App\Enums\OperationSupplierStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\ProductionStatusEnum;
use App\Enums\RoleEnum;
use App\Enums\SupplierUserRoleEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Production;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\Supplier\OperationProductionCompletedForSupplierNotification;
use App\Notifications\Supplier\OperationProductionReopenedForSupplierNotification;
use App\Services\OperationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

function buildCaseScenario(string $slug, string $status = OperationStatusEnum::PRODUCTION->value): array
{
    $building = Building::create([
        'name' => 'CPS ' . $slug,
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-CPS-' . strtoupper($slug),
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'cps-' . $slug,
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => $status,
        'batch_number' => 'BATCH-CPS-' . strtoupper($slug),
    ]);

    $supplier = Supplier::create([
        'name' => 'Supplier CPS ' . $slug,
        'vat' => null,
        'mail' => 'cps-' . $slug . '@test.test',
        'phone' => null,
        'address' => null,
        'cap' => null,
        'city' => null,
        'province' => null,
        'status' => 'active',
    ]);

    $operation->suppliers()->attach($supplier->id, [
        'status' => OperationSupplierStatusEnum::TO_CONTACT->value,
        'selected' => true,
        'selected_at' => now(),
    ]);

    return [$operation, $supplier];
}

function reloadWithPivot(Operation $operation, Supplier $supplier): Operation
{
    return Operation::query()
        ->where('id', $operation->id)
        ->withSupplierPivot($supplier->id)
        ->first();
}

// ---------------------------------------------------------------------------
// case_status accessor
// ---------------------------------------------------------------------------

test('case_status defaults to open when pivot has no supplier_completed_at', function () {
    [$operation, $supplier] = buildCaseScenario('open');
    expect(reloadWithPivot($operation, $supplier)->case_status)->toBe(CaseStatusEnum::OPEN->value);
});

test('case_status is completed when pivot has supplier_completed_at', function () {
    [$operation, $supplier] = buildCaseScenario('done');
    DB::table('operation_supplier')
        ->where('operation_id', $operation->id)
        ->where('supplier_id', $supplier->id)
        ->update(['supplier_completed_at' => now()]);

    expect(reloadWithPivot($operation, $supplier)->case_status)->toBe(CaseStatusEnum::COMPLETED->value);
});

// ---------------------------------------------------------------------------
// Admin — markCaseCompletedByAdmin / reopenCaseByAdmin
// ---------------------------------------------------------------------------

test('markCaseCompletedByAdmin allowed regardless of production status (no Production)', function () {
    [$operation, $supplier] = buildCaseScenario('no-prod', OperationStatusEnum::REQUESTED->value);
    $admin = User::factory()->create();

    OperationService::markCaseCompletedByAdmin($operation, $admin);

    $row = DB::table('operation_supplier')->where('operation_id', $operation->id)->where('supplier_id', $supplier->id)->first();
    expect($row->supplier_completed_at)->not->toBeNull();
});

test('markCaseCompletedByAdmin does NOT touch Production.status', function () {
    [$operation] = buildCaseScenario('keep-prod', OperationStatusEnum::PRODUCTION->value);
    $prod = Production::create([
        'operation_id' => $operation->id,
        'status' => ProductionStatusEnum::CONFIRMED->value,
    ]);
    $admin = User::factory()->create();

    OperationService::markCaseCompletedByAdmin($operation, $admin);

    $prod->refresh();
    expect($prod->status)->toBe(ProductionStatusEnum::CONFIRMED->value);
});

test('markCaseCompletedByAdmin is not idempotent (second call fails)', function () {
    [$operation] = buildCaseScenario('idem');
    $admin = User::factory()->create();

    OperationService::markCaseCompletedByAdmin($operation, $admin);

    expect(fn () => OperationService::markCaseCompletedByAdmin($operation, $admin))
        ->toThrow(ValidationException::class);
});

test('markCaseCompletedByAdmin logs activity with admin causer', function () {
    [$operation] = buildCaseScenario('log');
    $admin = User::factory()->create();

    OperationService::markCaseCompletedByAdmin($operation, $admin);

    expect(Activity::query()
        ->where('subject_id', $operation->id)
        ->where('event', 'case_completed')
        ->where('causer_id', $admin->id)
        ->exists())->toBeTrue();
});

test('markCaseCompletedByAdmin fails when no selected supplier', function () {
    $building = Building::create([
        'name' => 'CPS no-sup',
        'vat' => null, 'is_studio' => null, 'customer_code' => 'CUS-NO-SUP',
        'is_laboratory' => null, 'headquarter_address' => null, 'legal_address' => null,
        'approved' => true, 'fiscal_code' => null, 'sdi_code' => null, 'slug' => 'no-sup',
    ]);
    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::PRODUCTION->value,
        'batch_number' => 'BATCH-NO-SUP',
    ]);
    $admin = User::factory()->create();

    OperationService::markCaseCompletedByAdmin($operation, $admin);
})->throws(ValidationException::class);

test('reopenCaseByAdmin resets supplier_completed_at and logs activity', function () {
    [$operation, $supplier] = buildCaseScenario('reopen');
    DB::table('operation_supplier')
        ->where('operation_id', $operation->id)
        ->where('supplier_id', $supplier->id)
        ->update(['supplier_completed_at' => now()]);
    $admin = User::factory()->create();

    OperationService::reopenCaseByAdmin($operation, $admin);

    $row = DB::table('operation_supplier')->where('operation_id', $operation->id)->first();
    expect($row->supplier_completed_at)->toBeNull();
    expect(Activity::query()
        ->where('subject_id', $operation->id)
        ->where('event', 'case_reopened')
        ->where('causer_id', $admin->id)
        ->exists())->toBeTrue();
});

test('reopenCaseByAdmin fails when case is not completed', function () {
    [$operation] = buildCaseScenario('reopen-noop');
    $admin = User::factory()->create();

    OperationService::reopenCaseByAdmin($operation, $admin);
})->throws(ValidationException::class);

// ---------------------------------------------------------------------------
// Admin — produzione: matrice transizioni
// ---------------------------------------------------------------------------

test('confirmProduction creates Production with confirmed status when none exists', function () {
    Notification::fake();
    [$operation] = buildCaseScenario('first-confirm', OperationStatusEnum::WAITING_APPROVAL->value);

    OperationService::confirmProduction($operation->fresh());

    $prod = $operation->fresh()->productions()->oldest()->first();
    expect($prod->status)->toBe(ProductionStatusEnum::CONFIRMED->value);
});

test('confirmProduction is idempotent on Confermata', function () {
    Notification::fake();
    [$operation] = buildCaseScenario('idem-conf');
    $prod = Production::create(['operation_id' => $operation->id, 'status' => ProductionStatusEnum::CONFIRMED->value]);
    $confirmedAt = $prod->confirmed_at;

    OperationService::confirmProduction($operation->fresh());

    $prod->refresh();
    expect($prod->confirmed_at?->toIso8601String())->toBe($confirmedAt?->toIso8601String());
});

test('confirmProduction allowed from Annullata (riconferma)', function () {
    Notification::fake();
    [$operation] = buildCaseScenario('riconf');
    $prod = Production::create(['operation_id' => $operation->id, 'status' => ProductionStatusEnum::CANCELED->value]);

    OperationService::confirmProduction($operation->fresh());

    expect($prod->fresh()->status)->toBe(ProductionStatusEnum::CONFIRMED->value);
});

test('cancelProduction allowed from Confermata and DOES NOT touch supplier_completed_at', function () {
    Notification::fake();
    [$operation, $supplier] = buildCaseScenario('cancel-keep-pivot');
    Production::create(['operation_id' => $operation->id, 'status' => ProductionStatusEnum::CONFIRMED->value]);
    DB::table('operation_supplier')
        ->where('operation_id', $operation->id)
        ->where('supplier_id', $supplier->id)
        ->update(['supplier_completed_at' => now()]);

    OperationService::cancelProduction($operation->fresh());

    $row = DB::table('operation_supplier')->where('operation_id', $operation->id)->first();
    expect($row->supplier_completed_at)->not->toBeNull();
});

test('cancelProduction allowed from Completata', function () {
    Notification::fake();
    [$operation] = buildCaseScenario('cancel-from-completed');
    $prod = Production::create(['operation_id' => $operation->id, 'status' => ProductionStatusEnum::CONFIRMED->value]);
    OperationService::markProductionCompleted($operation->fresh());

    OperationService::cancelProduction($operation->fresh());

    expect($prod->fresh()->status)->toBe(ProductionStatusEnum::CANCELED->value);
});

test('markProductionCompleted only allowed from Confermata', function () {
    Notification::fake();
    [$operation] = buildCaseScenario('complete-from-null');

    OperationService::markProductionCompleted($operation->fresh());
})->throws(ValidationException::class);

test('markProductionCompleted moves Confermata to Completata', function () {
    Notification::fake();
    [$operation] = buildCaseScenario('complete-ok');
    $prod = Production::create(['operation_id' => $operation->id, 'status' => ProductionStatusEnum::CONFIRMED->value]);

    OperationService::markProductionCompleted($operation->fresh());

    $prod->refresh();
    expect($prod->status)->toBe(ProductionStatusEnum::COMPLETED->value);
    expect($prod->completed_at)->not->toBeNull();
});

test('markProductionCompleted notifies supplier', function () {
    Notification::fake();
    [$operation, $supplier] = buildCaseScenario('complete-notif');
    Production::create(['operation_id' => $operation->id, 'status' => ProductionStatusEnum::CONFIRMED->value]);
    $supplierUser = User::factory()->create(['last_login_at' => now()]);
    $supplierUser->makeSupplier();
    $supplier->users()->attach($supplierUser->id, [
        'role' => SupplierUserRoleEnum::ADMIN->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    OperationService::markProductionCompleted($operation->fresh());

    Notification::assertSentTo($supplierUser, OperationProductionCompletedForSupplierNotification::class);
});

test('reopenProduction only allowed from Completata', function () {
    Notification::fake();
    [$operation] = buildCaseScenario('reopen-prod-fail');
    Production::create(['operation_id' => $operation->id, 'status' => ProductionStatusEnum::CONFIRMED->value]);

    OperationService::reopenProduction($operation->fresh());
})->throws(ValidationException::class);

test('reopenProduction moves Completata to Confermata', function () {
    Notification::fake();
    [$operation] = buildCaseScenario('reopen-prod-ok');
    $prod = Production::create(['operation_id' => $operation->id, 'status' => ProductionStatusEnum::CONFIRMED->value]);
    OperationService::markProductionCompleted($operation->fresh());

    OperationService::reopenProduction($operation->fresh());

    $prod->refresh();
    expect($prod->status)->toBe(ProductionStatusEnum::CONFIRMED->value);
});

test('reopenProduction notifies supplier', function () {
    Notification::fake();
    [$operation, $supplier] = buildCaseScenario('reopen-prod-notif');
    Production::create(['operation_id' => $operation->id, 'status' => ProductionStatusEnum::CONFIRMED->value]);
    OperationService::markProductionCompleted($operation->fresh());
    $supplierUser = User::factory()->create(['last_login_at' => now()]);
    $supplierUser->makeSupplier();
    $supplier->users()->attach($supplierUser->id, [
        'role' => SupplierUserRoleEnum::ADMIN->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    OperationService::reopenProduction($operation->fresh());

    Notification::assertSentTo($supplierUser, OperationProductionReopenedForSupplierNotification::class);
});

// ---------------------------------------------------------------------------
// Payload — Fornitore vede solo production_status; Admin vede case_status nello show
// ---------------------------------------------------------------------------

test('supplier operations index payload exposes production_status (no case_status)', function () {
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => RoleEnum::SUPPLIER->value, 'guard_name' => 'web']);

    [$operation, $supplier] = buildCaseScenario('idx');
    Production::create(['operation_id' => $operation->id, 'status' => ProductionStatusEnum::CONFIRMED->value]);

    $admin = User::factory()->create();
    $admin->makeSupplier();
    $supplier->users()->attach($admin->id, [
        'role' => SupplierUserRoleEnum::ADMIN->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $response = test()->actingAs($admin)->get(route('workspace.supplier.operations.index'));
    $response->assertOk();

    $rendered = $response->viewData('page');
    $items = $rendered['props']['operations']['data'] ?? [];
    $found = collect($items)->firstWhere('id', $operation->id);

    expect($found)->not->toBeNull();
    expect($found['production_status'])->toBe(ProductionStatusEnum::CONFIRMED->value);
    expect($found)->not->toHaveKey('case_status');
});

test('admin show payload exposes case_status derived from supplier_completed_at', function () {
    [$operation] = buildCaseScenario('admin-case');
    $admin = User::factory()->create();

    OperationService::markCaseCompletedByAdmin($operation, $admin);

    $data = OperationService::getAdminShowData($operation->fresh());
    expect($data['operation']['case_status'])->toBe(CaseStatusEnum::COMPLETED->value);
    expect($data['operation']['supplier_completed_at'])->not->toBeNull();
});
