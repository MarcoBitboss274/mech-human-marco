<?php

use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\ProductionStatusEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Production;
use App\Models\User;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;

function createAdminUserForProductions(): User
{
    Role::query()->firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $user->makeAdmin();

    return $user;
}

function createOperationForProductions(): Operation
{
    $building = Building::create([
        'name' => 'Production CRUD Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-PROD-CRUD-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'production-crud-test',
    ]);

    return Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-PROD-CRUD',
    ]);
}

test('admin can create, update and soft delete production via CRUD routes', function () {
    $user = createAdminUserForProductions();
    $operation = createOperationForProductions();

    Carbon::setTestNow('2026-04-10 10:00:00');

    $this->actingAs($user)
        ->post('/productions', [
            'operation_id' => $operation->id,
            'status' => ProductionStatusEnum::CONFIRMED->value,
        ])
        ->assertRedirect(route('productions.index'));

    $production = Production::query()->firstOrFail();
    expect($production->operation_id)->toBe($operation->id);
    expect($production->status)->toBe(ProductionStatusEnum::CONFIRMED->value);
    expect($production->confirmed_at?->toDateTimeString())->toBe('2026-04-10 10:00:00');
    expect($production->canceled_at)->toBeNull();

    Carbon::setTestNow('2026-04-11 09:00:00');

    $this->actingAs($user)
        ->put("/productions/{$production->id}", [
            'operation_id' => $operation->id,
            'status' => ProductionStatusEnum::CANCELED->value,
        ])
        ->assertRedirect(route('productions.index'));

    $production->refresh();
    expect($production->status)->toBe(ProductionStatusEnum::CANCELED->value);
    expect($production->confirmed_at)->toBeNull();
    expect($production->canceled_at?->toDateTimeString())->toBe('2026-04-11 09:00:00');

    $this->actingAs($user)
        ->delete("/productions/{$production->id}")
        ->assertRedirect(route('productions.index'));

    expect(Production::query()->count())->toBe(0);
    expect(Production::withTrashed()->count())->toBe(1);

    Carbon::setTestNow();
});

