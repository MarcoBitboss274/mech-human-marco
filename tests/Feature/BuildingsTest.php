<?php

use App\Enums\RoleEnum;
use App\Models\Building;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $adminRole = Role::create(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);
    Permission::query()->create(['name' => 'buildings.index', 'guard_name' => 'web']);
    Permission::query()->create(['name' => 'buildings.create', 'guard_name' => 'web']);
    Permission::query()->create(['name' => 'buildings.edit', 'guard_name' => 'web']);
    Permission::query()->create(['name' => 'buildings.destroy', 'guard_name' => 'web']);

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRoleToUser($adminRole->name);
    $this->adminUser->givePermissionTo([
        'buildings.index',
        'buildings.create',
        'buildings.edit',
        'buildings.destroy',
    ]);
});

test('guests are redirected to the login page when accessing buildings', function () {
    $response = $this->get('/buildings');
    $response->assertRedirect('/login');
});

test('admin user can visit buildings index', function () {
    $this->actingAs($this->adminUser);

    $response = $this->get('/buildings');
    $response->assertStatus(200);
});

test('admin user can visit building show', function () {
    $this->actingAs($this->adminUser);

    $building = Building::create([
        'name' => 'Show Test Building',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-SHOW-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => false,
        'fiscal_code' => 'FSC-SHOW-001',
        'sdi_code' => 'SDI-SHOW-001',
    ]);

    $response = $this->get(route('buildings.show', ['building' => $building->id]));

    $response->assertStatus(200);
    $response->assertInertia(
        fn($page) => $page
            ->component('buildings/Show')
            ->has('building')
            ->has('users')
            ->where('building.customer_code', 'CUS-SHOW-001')
            ->where('building.fiscal_code', 'FSC-SHOW-001')
            ->where('building.sdi_code', 'SDI-SHOW-001')
            ->where('building.name', 'Show Test Building')
    );
});

test('admin user can create a building', function () {
    $this->actingAs($this->adminUser);

    $response = $this->post('/buildings', [
        'name' => 'Test Building',
        'vat' => '12345678901',
        'is_studio' => true,
        'customer_code' => 'CUS-ADM-001',
        'is_laboratory' => false,
        'headquarter_address' => 'Via Test 1',
        'legal_address' => 'Via Legale 1',
        'approved' => true,
        'fiscal_code' => 'FSC-ADM-001',
        'sdi_code' => 'SDI-ADM-001',
    ]);

    $response->assertRedirect(route('buildings.index'));
    $this->assertDatabaseHas('buildings', [
        'name' => 'Test Building',
        'vat' => '12345678901',
        'is_studio' => true,
        'customer_code' => 'CUS-ADM-001',
        'fiscal_code' => 'FSC-ADM-001',
        'sdi_code' => 'SDI-ADM-001',
        'is_laboratory' => false,
    ]);
});

test('admin user can update a building', function () {
    $this->actingAs($this->adminUser);

    $building = Building::create([
        'name' => 'Original Name',
        'vat' => '12345678901',
        'is_studio' => null,
        'customer_code' => 'CUS-OLD-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => false,
        'fiscal_code' => null,
        'sdi_code' => null,
    ]);

    $response = $this->put("/buildings/{$building->id}", [
        'name' => 'Updated Name',
        'vat' => $building->vat,
        'is_studio' => $building->is_studio,
        'customer_code' => 'CUS-NEW-001',
        'is_laboratory' => $building->is_laboratory,
        'headquarter_address' => $building->headquarter_address,
        'legal_address' => $building->legal_address,
        'approved' => true,
        'fiscal_code' => 'FSC-NEW-001',
        'sdi_code' => 'SDI-NEW-001',
    ]);

    $response->assertRedirect(route('buildings.index'));
    $building->refresh();
    expect($building->name)->toBe('Updated Name');
    expect($building->customer_code)->toBe('CUS-NEW-001');
    expect($building->fiscal_code)->toBe('FSC-NEW-001');
    expect($building->sdi_code)->toBe('SDI-NEW-001');
    expect($building->approved)->toBeTrue();
});

test('admin user can delete a building', function () {
    $this->actingAs($this->adminUser);

    $building = Building::create([
        'name' => 'To Delete',
        'vat' => '12345678901',
        'is_studio' => null,
        'customer_code' => 'CUS-DEL-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => false,
        'fiscal_code' => null,
        'sdi_code' => null,
    ]);

    $response = $this->delete("/buildings/{$building->id}");

    $response->assertRedirect(route('buildings.index'));
    $this->assertSoftDeleted($building);
});