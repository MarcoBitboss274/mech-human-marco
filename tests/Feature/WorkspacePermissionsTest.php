<?php

use App\Enums\BuildingUserRoleEnum;
use App\Enums\RoleEnum;
use App\Enums\WorkspaceAbilityEnum;
use App\Models\Building;
use App\Models\User;
use App\Services\WorkspaceAuthorizationService;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => RoleEnum::CUSTOMER->value, 'guard_name' => 'web']);
});

test('workspace ability is granted based on building pivot role', function () {
    $building = Building::create([
        'name' => 'Workspace Perm Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-WS-PERM-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'workspace-perm-test',
    ]);

    $admin = User::factory()->create([
        'odontoiatra' => true,
        'odontotecnico' => false,
    ]);
    $admin->makeCustomer();
    $admin->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::ADMIN->value,
    ]);

    $member = User::factory()->create([
        'odontoiatra' => true,
        'odontotecnico' => false,
    ]);
    $member->makeCustomer();
    $member->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::MEMBER->value,
    ]);

    $service = app(WorkspaceAuthorizationService::class);

    expect($service->canInBuilding($admin, $building, WorkspaceAbilityEnum::BUILDING_UPDATE->value))->toBeTrue();
    expect($service->canInBuilding($member, $building, WorkspaceAbilityEnum::BUILDING_UPDATE->value))->toBeFalse();
});

test('workspace ability can depend on user capability boolean', function () {
    $building = Building::create([
        'name' => 'Workspace Capability Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-WS-CAP-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'workspace-capability-test',
    ]);

    $dentist = User::factory()->create([
        'odontoiatra' => true,
        'odontotecnico' => false,
    ]);
    $dentist->makeCustomer();
    $dentist->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::ADMIN->value,
    ]);

    $nonDentist = User::factory()->create([
        'odontoiatra' => false,
        'odontotecnico' => true,
    ]);
    $nonDentist->makeCustomer();
    $nonDentist->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::ADMIN->value,
    ]);

    $service = app(WorkspaceAuthorizationService::class);

    expect($service->canInBuilding($dentist, $building, WorkspaceAbilityEnum::PRESCRIPTIONS_SEND->value))->toBeTrue();
    expect($service->canInBuilding($nonDentist, $building, WorkspaceAbilityEnum::PRESCRIPTIONS_SEND->value))->toBeFalse();
});

