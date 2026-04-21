<?php

use App\Enums\BuildingUserRoleEnum;
use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\RoleEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Prescription;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => RoleEnum::CUSTOMER->value, 'guard_name' => 'web']);
});

function workspaceWizardPayload(int $buildingId, bool $draft, ?int $userId): array
{
    return [
        'draft' => $draft,
        'building_id' => $buildingId,
        'user_id' => $userId,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'REF-WS-OPS-001',
        'name' => 'Mario',
        'surname' => 'Rossi',
        'age' => 35,
        'gender' => null,
        'company_name' => null,
        'address' => null,
        'city' => null,
        'province' => null,
        'cap' => null,
        'notes' => null,
        'lybra_aligner_details' => [
            'cut_line' => 'line_a',
            'note' => 'Test note',
        ],
    ];
}

test('workspace wizard store forces requester to current user for member', function () {
    /** @var \Tests\TestCase $this */
    $building = Building::create([
        'name' => 'WS Ops Wizard Requester Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-WS-OPS-REQ-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'ws-ops-wizard-requester-test',
    ]);

    /** @var User $member */
    $member = User::factory()->create([
        'odontoiatra' => true,
        'odontotecnico' => false,
    ]);
    $member->makeCustomer();
    $member->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::MEMBER->value,
    ]);

    $otherUser = User::factory()->create();

    $this->actingAs($member);

    $response = $this->post(route('workspace.operations.store-wizard', [
        'building' => $building->slug,
    ]), workspaceWizardPayload($building->id, true, $otherUser->id));

    $operation = Operation::query()->latest('id')->first();
    $prescription = Prescription::query()
        ->where('operation_id', $operation?->id)
        ->latest('id')
        ->first();

    $response->assertRedirect(route('workspace.operations.show', [
        'building' => $building->slug,
        'operation' => $operation?->id,
    ]));

    expect($operation)->not->toBeNull();
    expect($prescription)->not->toBeNull();
    expect((int) $prescription->user_id)->toBe((int) $member->id);
});

test('workspace wizard update ignores requester changes for member', function () {
    /** @var \Tests\TestCase $this */
    $building = Building::create([
        'name' => 'WS Ops Wizard Requester Update Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-WS-OPS-REQ-002',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'ws-ops-wizard-requester-update-test',
    ]);

    /** @var User $member */
    $member = User::factory()->create([
        'odontoiatra' => true,
        'odontotecnico' => false,
    ]);
    $member->makeCustomer();
    $member->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::MEMBER->value,
    ]);

    $requester = User::factory()->create();

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
    ]);

    $prescription = Prescription::create([
        'operation_id' => $operation->id,
        'building_id' => $building->id,
        'user_id' => $requester->id,
        'status' => PrescriptionStatusEnum::DRAFT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'REF-WS-OPS-EDIT-001',
        'name' => 'Mario',
        'surname' => 'Rossi',
        'age' => 35,
        'gender' => null,
    ]);

    $this->actingAs($member);

    $response = $this->put(route('workspace.operations.update-wizard', [
        'building' => $building->slug,
        'operation' => $operation->id,
    ]), workspaceWizardPayload($building->id, true, $member->id));

    $response->assertRedirect(route('workspace.operations.show', [
        'building' => $building->slug,
        'operation' => $operation->id,
    ]));

    $prescription->refresh();
    expect((int) $prescription->user_id)->toBe((int) $requester->id);
});

