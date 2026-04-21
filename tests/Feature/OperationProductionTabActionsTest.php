<?php

use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\ProductionStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Production;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function createAdminWithProductionManagePermission(): User
{
    $adminRole = Role::create(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);

    Permission::create(['name' => 'operations.index', 'guard_name' => 'web']);
    Permission::create(['name' => 'operations.production.manage', 'guard_name' => 'web']);

    $adminRole->givePermissionTo(['operations.index', 'operations.production.manage']);

    /** @var User $adminUser */
    $adminUser = User::factory()->create();
    $adminUser->assignRoleToUser($adminRole->name);

    return $adminUser;
}

function createOperationForProductionTab(): Operation
{
    $building = Building::create([
        'name' => 'Operation Production Tab Actions Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-OPS-PROD-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'slug' => 'operation-production-tab-actions-test',
    ]);

    return Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-OPS-PROD-001',
    ]);
}

test('confirm production creates production confirmed and sets operation status to production', function () {
    $adminUser = createAdminWithProductionManagePermission();
    $operation = createOperationForProductionTab();

    $this->actingAs($adminUser)
        ->post(route('operations.productions.confirm', ['operation' => $operation->id]))
        ->assertRedirect(route('operations.show', ['operation' => $operation->id]));

    $production = Production::query()->firstOrFail();
    expect($production->operation_id)->toBe($operation->id);
    expect($production->status)->toBe(ProductionStatusEnum::CONFIRMED->value);

    $operation->refresh();
    expect($operation->status)->toBe(OperationStatusEnum::PRODUCTION->value);
});

test('confirm production updates first existing production to confirmed', function () {
    $adminUser = createAdminWithProductionManagePermission();
    $operation = createOperationForProductionTab();

    $production = Production::query()->create([
        'operation_id' => $operation->id,
        'status' => ProductionStatusEnum::CANCELED->value,
    ]);

    $this->actingAs($adminUser)
        ->post(route('operations.productions.confirm', ['operation' => $operation->id]))
        ->assertRedirect(route('operations.show', ['operation' => $operation->id]));

    $production->refresh();
    expect($production->status)->toBe(ProductionStatusEnum::CONFIRMED->value);
    expect(Production::query()->count())->toBe(1);
});

test('cancel production sets first production to canceled', function () {
    $adminUser = createAdminWithProductionManagePermission();
    $operation = createOperationForProductionTab();

    $production = Production::query()->create([
        'operation_id' => $operation->id,
        'status' => ProductionStatusEnum::CONFIRMED->value,
    ]);

    $this->actingAs($adminUser)
        ->post(route('operations.productions.cancel', ['operation' => $operation->id]))
        ->assertRedirect(route('operations.show', ['operation' => $operation->id]));

    $production->refresh();
    expect($production->status)->toBe(ProductionStatusEnum::CANCELED->value);
});

test('confirm production is forbidden without operations.production.manage', function () {
    $operation = createOperationForProductionTab();

    $role = Role::create(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);
    Permission::create(['name' => 'operations.index', 'guard_name' => 'web']);
    $role->givePermissionTo(['operations.index']);

    /** @var User $user */
    $user = User::factory()->create();
    $user->assignRoleToUser($role->name);

    $this->actingAs($user)
        ->post(route('operations.productions.confirm', ['operation' => $operation->id]))
        ->assertNotFound();
});

