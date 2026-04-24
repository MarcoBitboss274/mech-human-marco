<?php

use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Prescription;
use App\Models\User;
use Carbon\Carbon;

function buildPrescriptionConfirmedAtScenario(string $slug): Prescription
{
    $building = Building::create([
        'name' => 'Prescription Confirmed At Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-PRE-CONF-' . strtoupper($slug),
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'prescription-confirmed-at-' . $slug,
    ]);

    $user = User::factory()->create();

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-PRE-CONF-' . strtoupper($slug),
    ]);

    return Prescription::create([
        'operation_id' => $operation->id,
        'building_id' => $building->id,
        'user_id' => $user->id,
        'status' => PrescriptionStatusEnum::DRAFT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'PRE-CONF-' . strtoupper($slug),
        'name' => 'Test',
        'surname' => 'Patient',
    ]);
}

test('prescription confirmed_at is set on transition to confirmed and cleared when leaving', function () {
    $prescription = buildPrescriptionConfirmedAtScenario('a');
    expect($prescription->fresh()->confirmed_at)->toBeNull();

    Carbon::setTestNow('2026-04-10 09:00:00');
    $prescription->update(['status' => PrescriptionStatusEnum::SENT->value]);
    $prescription->refresh();
    expect($prescription->confirmed_at)->toBeNull();

    Carbon::setTestNow('2026-04-12 14:30:00');
    $prescription->update(['status' => PrescriptionStatusEnum::CONFIRMED->value]);
    $prescription->refresh();
    expect($prescription->confirmed_at?->toDateTimeString())->toBe('2026-04-12 14:30:00');

    Carbon::setTestNow('2026-04-13 08:00:00');
    $prescription->update(['ref' => 'CHANGED-REF']);
    $prescription->refresh();
    expect($prescription->confirmed_at?->toDateTimeString())->toBe('2026-04-12 14:30:00');

    $prescription->update(['status' => PrescriptionStatusEnum::SENT->value]);
    $prescription->refresh();
    expect($prescription->confirmed_at)->toBeNull();

    Carbon::setTestNow();
});

test('prescription created as confirmed gets confirmed_at', function () {
    Carbon::setTestNow('2026-05-01 11:00:00');
    $building = Building::create([
        'name' => 'Prescription Confirmed Create',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-PRE-CONF-NEW',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'prescription-confirmed-create',
    ]);
    $user = User::factory()->create();
    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-PRE-CONF-NEW',
    ]);

    $prescription = Prescription::create([
        'operation_id' => $operation->id,
        'building_id' => $building->id,
        'user_id' => $user->id,
        'status' => PrescriptionStatusEnum::CONFIRMED->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'PRE-CONF-NEW',
        'name' => 'Test',
        'surname' => 'Patient',
    ]);

    expect($prescription->fresh()->confirmed_at?->toDateTimeString())->toBe('2026-05-01 11:00:00');

    Carbon::setTestNow();
});
