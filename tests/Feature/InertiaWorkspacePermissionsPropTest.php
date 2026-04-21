<?php

use App\Enums\BuildingUserRoleEnum;
use App\Enums\RoleEnum;
use App\Models\Building;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => RoleEnum::CUSTOMER->value, 'guard_name' => 'web']);
    Role::create(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);
});

test('inertia shared auth.workspacePermissions is array for customer in workspace', function () {
    $building = Building::create([
        'name' => 'Workspace Prop Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-WS-PROP-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'workspace-prop-test',
    ]);

    $customer = User::factory()->create();
    $customer->makeCustomer();
    $customer->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::ADMIN->value,
    ]);

    $this->actingAs($customer);

    $response = $this->get(route('workspace.building.index', [
        'building' => $building->slug,
    ]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->has('auth.workspacePermissions'));
});

test('inertia shared auth.workspacePermissions is null for non-customer', function () {
    $admin = User::factory()->create();
    $admin->assignRoleToUser(RoleEnum::ADMIN->value);

    $this->actingAs($admin);

    $response = $this->get('/dashboard');
    $response->assertStatus(200);

    $response->assertInertia(fn ($page) => $page->where('auth.workspacePermissions', null));
});

