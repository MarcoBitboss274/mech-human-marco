<?php

use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\ProductionStatusEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Production;
use Carbon\Carbon;

test('production confirmed_at/canceled_at are synced based on status transitions', function () {
    $building = Building::create([
        'name' => 'Production Status Timestamps Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-PROD-TS-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'production-status-timestamps-test',
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-PROD-TS',
    ]);

    $production = Production::create([
        'operation_id' => $operation->id,
        'status' => null,
    ]);
    expect($production->fresh()->confirmed_at)->toBeNull();
    expect($production->fresh()->canceled_at)->toBeNull();

    Carbon::setTestNow('2026-04-01 10:00:00');
    $production->update(['status' => ProductionStatusEnum::CONFIRMED->value]);
    $production->refresh();
    expect($production->confirmed_at?->toDateTimeString())->toBe('2026-04-01 10:00:00');
    expect($production->canceled_at)->toBeNull();

    Carbon::setTestNow('2026-04-02 15:00:00');
    $production->update(['operation_id' => $operation->id]);
    $production->refresh();
    expect($production->confirmed_at?->toDateTimeString())->toBe('2026-04-01 10:00:00');
    expect($production->canceled_at)->toBeNull();

    Carbon::setTestNow('2026-04-03 09:30:00');
    $production->update(['status' => ProductionStatusEnum::CANCELED->value]);
    $production->refresh();
    expect($production->confirmed_at)->toBeNull();
    expect($production->canceled_at?->toDateTimeString())->toBe('2026-04-03 09:30:00');

    $production->update(['status' => null]);
    $production->refresh();
    expect($production->confirmed_at)->toBeNull();
    expect($production->canceled_at)->toBeNull();

    Carbon::setTestNow();
});

test('production created as confirmed gets confirmed_at and clears canceled_at', function () {
    $building = Building::create([
        'name' => 'Production Confirmed Create Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-PROD-TS-002',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'production-confirmed-create-test',
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-PROD-TS-2',
    ]);

    Carbon::setTestNow('2026-05-01 08:00:00');
    $production = Production::create([
        'operation_id' => $operation->id,
        'status' => ProductionStatusEnum::CONFIRMED->value,
    ]);

    expect($production->fresh()->confirmed_at?->toDateTimeString())->toBe('2026-05-01 08:00:00');
    expect($production->fresh()->canceled_at)->toBeNull();

    Carbon::setTestNow();
});

test('production created as canceled gets canceled_at and clears confirmed_at', function () {
    $building = Building::create([
        'name' => 'Production Canceled Create Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-PROD-TS-003',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'production-canceled-create-test',
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-PROD-TS-3',
    ]);

    Carbon::setTestNow('2026-05-02 09:00:00');
    $production = Production::create([
        'operation_id' => $operation->id,
        'status' => ProductionStatusEnum::CANCELED->value,
    ]);

    expect($production->fresh()->confirmed_at)->toBeNull();
    expect($production->fresh()->canceled_at?->toDateTimeString())->toBe('2026-05-02 09:00:00');

    Carbon::setTestNow();
});

