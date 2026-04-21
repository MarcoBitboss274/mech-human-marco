<?php

use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\RoleEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::query()->create(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);
    Role::query()->create(['name' => RoleEnum::AGENT->value, 'guard_name' => 'web']);

    Permission::query()->create(['name' => 'operations.index', 'guard_name' => 'web']);
    Permission::query()->create(['name' => 'operations.view-all', 'guard_name' => 'web']);
});

function makeAgentUser(array $permissions = []): User
{
    $user = User::factory()->create();
    $user->assignRoleToUser(RoleEnum::AGENT->value);

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

function makeAdminUserForAdminOperations(array $permissions = []): User
{
    $user = User::factory()->create();
    $user->assignRoleToUser(RoleEnum::ADMIN->value);

    if ($permissions !== []) {
        $user->givePermissionTo($permissions);
    }

    return $user;
}

test('agent without operations.view-all sees only operations for own managed buildings in admin index', function () {
    /** @var \Tests\TestCase $this */
    $agent = makeAgentUser(['operations.index']);

    $buildingAllowed = Building::create([
        'agent_id' => $agent->id,
        'name' => 'Agent Building A',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-AG-A',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'agent-building-a',
    ]);

    $buildingDenied = Building::create([
        'agent_id' => null,
        'name' => 'Agent Building B',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-AG-B',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'agent-building-b',
    ]);

    $allowedOperation = Operation::create([
        'building_id' => $buildingAllowed->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'AG-ALLOW-001',
    ]);

    Operation::create([
        'building_id' => $buildingDenied->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'AG-DENY-001',
    ]);

    $response = $this
        ->actingAs($agent)
        ->get(route('operations.index'));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('operations.data', 1)
        ->where('operations.data.0.id', $allowedOperation->id)
    );
});

test('agent without operations.view-all cannot access admin show for operation outside own managed buildings', function () {
    /** @var \Tests\TestCase $this */
    $agent = makeAgentUser(['operations.index']);

    $buildingDenied = Building::create([
        'agent_id' => null,
        'name' => 'Denied Building',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-AG-DENY',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'denied-building',
    ]);

    $operation = Operation::create([
        'building_id' => $buildingDenied->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'AG-DENY-SHOW-001',
    ]);

    $this
        ->actingAs($agent)
        ->get(route('operations.show', ['operation' => $operation->id]))
        ->assertNotFound();
});

test('admin with operations.view-all can access admin index and show without agent building restrictions', function () {
    /** @var \Tests\TestCase $this */
    $admin = makeAdminUserForAdminOperations(['operations.index', 'operations.view-all']);

    $building = Building::create([
        'agent_id' => null,
        'name' => 'Any Building',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-ADMIN-ANY',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'any-building',
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'ADMIN-ANY-001',
    ]);

    $this
        ->actingAs($admin)
        ->get(route('operations.index'))
        ->assertStatus(200);

    $this
        ->actingAs($admin)
        ->get(route('operations.show', ['operation' => $operation->id]))
        ->assertStatus(200);
});

