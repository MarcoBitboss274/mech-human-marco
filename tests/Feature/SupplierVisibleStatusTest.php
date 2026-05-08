<?php

use App\Enums\OperationStatusEnum;
use App\Enums\OperationSupplierStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\SupplierVisibleStatusEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\Admin\SupplierProductionCompletedForAdmin;
use App\Services\OperationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function buildSupplierVisibleScenario(string $slug, string $status = OperationStatusEnum::REQUESTED->value): array
{
    $building = Building::create([
        'name' => 'SVS ' . $slug,
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-SVS-' . strtoupper($slug),
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'svs-' . $slug,
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => $status,
        'batch_number' => 'BATCH-SVS-' . strtoupper($slug),
    ]);

    $supplier = Supplier::create([
        'name' => 'Supplier SVS ' . $slug,
        'vat' => null,
        'mail' => 'svs-' . $slug . '@test.test',
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

function reloadOperationWithPivot(Operation $operation, Supplier $supplier): Operation
{
    return Operation::query()
        ->where('id', $operation->id)
        ->withSupplierPivot($supplier->id)
        ->first();
}

test('DRAFT operation is not visible to supplier', function () {
    [$operation, $supplier] = buildSupplierVisibleScenario('draft', OperationStatusEnum::DRAFT->value);
    $fresh = reloadOperationWithPivot($operation, $supplier);
    expect($fresh->supplier_visible_status)->toBeNull();
});

test('REQUESTED maps to new_case', function () {
    [$operation, $supplier] = buildSupplierVisibleScenario('req', OperationStatusEnum::REQUESTED->value);
    $fresh = reloadOperationWithPivot($operation, $supplier);
    expect($fresh->supplier_visible_status)->toBe(SupplierVisibleStatusEnum::NEW_CASE->value);
});

test('IN_PROGRESS and WAITING_APPROVAL map to new_case', function () {
    [$op1, $s1] = buildSupplierVisibleScenario('inprog', OperationStatusEnum::IN_PROGRESS->value);
    [$op2, $s2] = buildSupplierVisibleScenario('waitap', OperationStatusEnum::WAITING_APPROVAL->value);
    expect(reloadOperationWithPivot($op1, $s1)->supplier_visible_status)
        ->toBe(SupplierVisibleStatusEnum::NEW_CASE->value);
    expect(reloadOperationWithPivot($op2, $s2)->supplier_visible_status)
        ->toBe(SupplierVisibleStatusEnum::NEW_CASE->value);
});

test('PRODUCTION maps to production_confirmed when not yet supplier-completed', function () {
    [$operation, $supplier] = buildSupplierVisibleScenario('prod', OperationStatusEnum::PRODUCTION->value);
    expect(reloadOperationWithPivot($operation, $supplier)->supplier_visible_status)
        ->toBe(SupplierVisibleStatusEnum::PRODUCTION_CONFIRMED->value);
});

test('COMPLETED operation without supplier_completed_at still maps to production_confirmed', function () {
    [$operation, $supplier] = buildSupplierVisibleScenario('compl', OperationStatusEnum::COMPLETED->value);
    expect(reloadOperationWithPivot($operation, $supplier)->supplier_visible_status)
        ->toBe(SupplierVisibleStatusEnum::PRODUCTION_CONFIRMED->value);
});

test('supplier_completed_at on pivot makes status COMPLETED regardless of operation status', function () {
    [$operation, $supplier] = buildSupplierVisibleScenario('done', OperationStatusEnum::PRODUCTION->value);
    DB::table('operation_supplier')
        ->where('operation_id', $operation->id)
        ->where('supplier_id', $supplier->id)
        ->update(['supplier_completed_at' => now()]);

    expect(reloadOperationWithPivot($operation, $supplier)->supplier_visible_status)
        ->toBe(SupplierVisibleStatusEnum::COMPLETED->value);
});

test('markSupplierProductionCompleted requires PRODUCTION status', function () {
    Notification::fake();
    [$operation, $supplier] = buildSupplierVisibleScenario('notprod', OperationStatusEnum::IN_PROGRESS->value);
    $user = User::factory()->create();

    OperationService::markSupplierProductionCompleted($operation, $supplier, $user);
})->throws(ValidationException::class);

test('markSupplierProductionCompleted is idempotent (second call fails)', function () {
    Notification::fake();
    [$operation, $supplier] = buildSupplierVisibleScenario('idem', OperationStatusEnum::PRODUCTION->value);
    $user = User::factory()->create();

    OperationService::markSupplierProductionCompleted($operation, $supplier, $user);

    expect(fn () => OperationService::markSupplierProductionCompleted($operation, $supplier, $user))
        ->toThrow(ValidationException::class);
});

test('markSupplierProductionCompleted sends admin notification', function () {
    Notification::fake();
    [$operation, $supplier] = buildSupplierVisibleScenario('notif', OperationStatusEnum::PRODUCTION->value);
    $user = User::factory()->create();
    $admin = User::factory()->create(['role' => \App\Enums\RoleEnum::ADMIN->value]);

    OperationService::markSupplierProductionCompleted($operation, $supplier, $user);

    Notification::assertSentTo($admin, SupplierProductionCompletedForAdmin::class);
});

test('cancelProduction resets supplier_completed_at and logs reset event', function () {
    Notification::fake();
    [$operation, $supplier] = buildSupplierVisibleScenario('rst', OperationStatusEnum::PRODUCTION->value);
    \App\Models\Production::create([
        'operation_id' => $operation->id,
        'status' => \App\Enums\ProductionStatusEnum::CONFIRMED->value,
    ]);
    DB::table('operation_supplier')
        ->where('operation_id', $operation->id)
        ->where('supplier_id', $supplier->id)
        ->update(['supplier_completed_at' => now()]);

    OperationService::cancelProduction($operation->fresh());

    $row = DB::table('operation_supplier')
        ->where('operation_id', $operation->id)
        ->where('supplier_id', $supplier->id)
        ->first();
    expect($row->supplier_completed_at)->toBeNull();

    expect(\Spatie\Activitylog\Models\Activity::query()
        ->where('subject_id', $operation->id)
        ->where('event', 'supplier_completed_reset_for_production_cancel')
        ->exists())->toBeTrue();
});

test('cancel operation does NOT reset supplier_completed_at', function () {
    Notification::fake();
    [$operation, $supplier] = buildSupplierVisibleScenario('cancel-op', OperationStatusEnum::PRODUCTION->value);
    DB::table('operation_supplier')
        ->where('operation_id', $operation->id)
        ->where('supplier_id', $supplier->id)
        ->update(['supplier_completed_at' => now()]);

    OperationService::cancel($operation->fresh());

    $row = DB::table('operation_supplier')
        ->where('operation_id', $operation->id)
        ->where('supplier_id', $supplier->id)
        ->first();
    expect($row->supplier_completed_at)->not->toBeNull();
});

test('uploadSupplierDocument blocks supplier in PRODUCTION', function () {
    [$operation, $supplier] = buildSupplierVisibleScenario('upload-block', OperationStatusEnum::PRODUCTION->value);
    $user = User::factory()->create();
    $file = \Illuminate\Http\UploadedFile::fake()->create('doc.pdf', 100);

    OperationService::uploadSupplierDocument($operation, $file, $user, asAdmin: false);
})->throws(ValidationException::class);

test('uploadSupplierDocument allowed for supplier in WAITING_APPROVAL', function () {
    [$operation, $supplier] = buildSupplierVisibleScenario('upload-ok', OperationStatusEnum::WAITING_APPROVAL->value);
    $user = User::factory()->create();
    $file = \Illuminate\Http\UploadedFile::fake()->create('doc.pdf', 100);

    $media = OperationService::uploadSupplierDocument($operation, $file, $user, asAdmin: false);

    expect($media->collection_name)->toBe('supplier_documents');
});

test('markSupplierProductionCompleted updates Production.status to completed and sets completed_at', function () {
    Notification::fake();
    [$operation, $supplier] = buildSupplierVisibleScenario('prod-completed', OperationStatusEnum::PRODUCTION->value);
    \App\Models\Production::create([
        'operation_id' => $operation->id,
        'status' => \App\Enums\ProductionStatusEnum::CONFIRMED->value,
    ]);
    $user = User::factory()->create();

    OperationService::markSupplierProductionCompleted($operation, $supplier, $user);

    $production = $operation->productions()->oldest()->first();
    expect($production->status)->toBe(\App\Enums\ProductionStatusEnum::COMPLETED->value);
    expect($production->completed_at)->not->toBeNull();
    expect($production->confirmed_at)->not->toBeNull();
});

test('operations index payload exposes supplier_visible_status = completed when pivot is completed', function () {
    Notification::fake();
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => \App\Enums\RoleEnum::SUPPLIER->value, 'guard_name' => 'web']);

    [$operation, $supplier] = buildSupplierVisibleScenario('idx-completed', OperationStatusEnum::PRODUCTION->value);
    \App\Models\Production::create([
        'operation_id' => $operation->id,
        'status' => \App\Enums\ProductionStatusEnum::CONFIRMED->value,
    ]);

    // Crea utente supplier admin associato al fornitore (Spatie role)
    $admin = User::factory()->create();
    $admin->makeSupplier();
    $supplier->users()->attach($admin->id, [
        'role' => \App\Enums\SupplierUserRoleEnum::ADMIN->value,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    OperationService::markSupplierProductionCompleted($operation, $supplier, $admin);

    $response = test()->actingAs($admin)->get(route('workspace.supplier.operations.index'));
    $response->assertOk();

    $rendered = $response->viewData('page');
    $items = $rendered['props']['operations']['data'] ?? [];
    $found = collect($items)->firstWhere('id', $operation->id);

    expect($found)->not->toBeNull();
    expect($found['supplier_visible_status'])->toBe('completed');
});

test('cancelProduction on completed Production clears confirmed_at and completed_at', function () {
    Notification::fake();
    [$operation, $supplier] = buildSupplierVisibleScenario('cancel-after-completed', OperationStatusEnum::PRODUCTION->value);
    $production = \App\Models\Production::create([
        'operation_id' => $operation->id,
        'status' => \App\Enums\ProductionStatusEnum::CONFIRMED->value,
    ]);
    $user = User::factory()->create();

    OperationService::markSupplierProductionCompleted($operation, $supplier, $user);

    OperationService::cancelProduction($operation->fresh());

    $production->refresh();
    expect($production->status)->toBe(\App\Enums\ProductionStatusEnum::CANCELED->value);
    expect($production->confirmed_at)->toBeNull();
    expect($production->completed_at)->toBeNull();
    expect($production->canceled_at)->not->toBeNull();
});
