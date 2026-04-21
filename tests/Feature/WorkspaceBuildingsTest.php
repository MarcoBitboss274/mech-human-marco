<?php

use App\Enums\BuildingUserRoleEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => RoleEnum::CUSTOMER->value, 'guard_name' => 'web']);
    $this->customerUser = User::factory()->create();
    $this->customerUser->makeCustomer();
});

test('customer can create a building from workspace', function () {
    $this->actingAs($this->customerUser);

    $response = $this->post('/workspace/buildings', [
        'name' => 'Workspace Building',
        'vat' => '98765432109',
        'is_studio' => false,
        'customer_code' => 'CUS-WS-001',
        'is_laboratory' => true,
        'headquarter_address' => 'Via Workspace 1',
        'legal_address' => 'Via Legale Workspace 1',
        'fiscal_code' => 'FSC-WS-001',
        'sdi_code' => 'SDI-WS-001',
    ]);

    $response->assertRedirect(route('workspace.dashboard'));

    $this->assertDatabaseHas('buildings', [
        'name' => 'Workspace Building',
        'vat' => '98765432109',
        'is_studio' => false,
        'customer_code' => 'CUS-WS-001',
        'fiscal_code' => 'FSC-WS-001',
        'sdi_code' => 'SDI-WS-001',
        'is_laboratory' => true,
        'approved' => false,
    ]);

    $building = \App\Models\Building::where('name', 'Workspace Building')->first();
    $this->assertDatabaseHas('building_user', [
        'user_id' => $this->customerUser->id,
        'building_id' => $building->id,
        'role' => BuildingUserRoleEnum::ADMIN->value,
    ]);
});
