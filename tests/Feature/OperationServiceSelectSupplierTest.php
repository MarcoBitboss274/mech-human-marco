<?php

use App\Enums\OperationStatusEnum;
use App\Enums\OperationSupplierStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Supplier;
use App\Services\OperationService;
use Carbon\Carbon;

function buildSelectSupplierScenario(string $slug): array
{
    $building = Building::create([
        'name' => 'Select Supplier Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-SEL-' . strtoupper($slug),
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'select-supplier-' . $slug,
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-SEL-' . strtoupper($slug),
    ]);

    $suppliers = collect(['A', 'B', 'C'])->map(fn ($key) => Supplier::create([
        'name' => "Supplier $key",
        'vat' => null,
        'mail' => strtolower($key) . '@sel.test',
        'phone' => null,
        'address' => null,
        'cap' => null,
        'city' => null,
        'province' => null,
        'status' => 'active',
    ]));

    foreach ($suppliers as $supplier) {
        $operation->suppliers()->attach($supplier->id, [
            'status' => OperationSupplierStatusEnum::TO_CONTACT->value,
            'selected' => false,
        ]);
    }

    return [$operation, $suppliers];
}

test('selectSupplier sets selected_at on the chosen pivot only', function () {
    [$operation, $suppliers] = buildSelectSupplierScenario('a');

    Carbon::setTestNow('2026-04-15 10:00:00');
    OperationService::selectSupplier($operation, $suppliers[0]);

    $operation->load('suppliers');
    $pivots = $operation->suppliers->keyBy('id');

    expect($pivots[$suppliers[0]->id]->pivot->selected)->toBeTruthy();
    expect($pivots[$suppliers[0]->id]->pivot->selected_at)->toBe('2026-04-15 10:00:00');
    expect($pivots[$suppliers[1]->id]->pivot->selected_at)->toBeNull();
    expect($pivots[$suppliers[2]->id]->pivot->selected_at)->toBeNull();

    Carbon::setTestNow();
});

test('selectSupplier on a different supplier resets the previous selected_at', function () {
    [$operation, $suppliers] = buildSelectSupplierScenario('b');

    Carbon::setTestNow('2026-04-15 10:00:00');
    OperationService::selectSupplier($operation, $suppliers[0]);

    Carbon::setTestNow('2026-04-16 11:00:00');
    OperationService::selectSupplier($operation, $suppliers[1]);

    $operation->load('suppliers');
    $pivots = $operation->suppliers->keyBy('id');

    expect($pivots[$suppliers[0]->id]->pivot->selected)->toBeFalsy();
    expect($pivots[$suppliers[0]->id]->pivot->selected_at)->toBeNull();
    expect($pivots[$suppliers[1]->id]->pivot->selected)->toBeTruthy();
    expect($pivots[$suppliers[1]->id]->pivot->selected_at)->toBe('2026-04-16 11:00:00');

    Carbon::setTestNow();
});
