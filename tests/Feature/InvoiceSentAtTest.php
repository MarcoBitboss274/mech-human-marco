<?php

use App\Enums\InvoiceStatusEnum;
use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Operation;
use Carbon\Carbon;

test('invoice sent_at is set on first transition to sent and cleared when leaving sent', function () {
    $building = Building::create([
        'name' => 'Invoice Sent At Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-INV-SENT-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'invoice-sent-at-test',
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-INV-SENT',
    ]);

    $invoice = Invoice::create([
        'operation_id' => $operation->id,
        'status' => InvoiceStatusEnum::DRAFT->value,
        'code' => 'INV-SAT-001',
        'amount' => 10,
        'description' => 'Test',
    ]);
    expect($invoice->fresh()->sent_at)->toBeNull();

    Carbon::setTestNow('2026-04-01 10:00:00');
    $invoice->update(['status' => InvoiceStatusEnum::SENT->value]);
    $invoice->refresh();
    expect($invoice->sent_at?->toDateTimeString())->toBe('2026-04-01 10:00:00');

    Carbon::setTestNow('2026-04-02 15:00:00');
    $invoice->update(['description' => 'Updated only']);
    $invoice->refresh();
    expect($invoice->sent_at?->toDateTimeString())->toBe('2026-04-01 10:00:00');

    $invoice->update(['status' => InvoiceStatusEnum::DRAFT->value]);
    $invoice->refresh();
    expect($invoice->sent_at)->toBeNull();

    Carbon::setTestNow();
});

test('invoice canceled after sent preserves sent_at and gets canceled_at', function () {
    $building = Building::create([
        'name' => 'Invoice Canceled Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-INV-CAN-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'invoice-canceled-test',
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-INV-CAN',
    ]);

    Carbon::setTestNow('2026-04-01 10:00:00');
    $invoice = Invoice::create([
        'operation_id' => $operation->id,
        'status' => InvoiceStatusEnum::SENT->value,
        'code' => 'INV-CAN-001',
        'amount' => 50,
        'description' => 'To be canceled',
    ]);
    expect($invoice->fresh()->sent_at?->toDateTimeString())->toBe('2026-04-01 10:00:00');

    Carbon::setTestNow('2026-04-05 12:00:00');
    $invoice->update(['status' => InvoiceStatusEnum::CANCELED->value]);
    $invoice->refresh();
    expect($invoice->canceled_at?->toDateTimeString())->toBe('2026-04-05 12:00:00');
    expect($invoice->sent_at?->toDateTimeString())->toBe('2026-04-01 10:00:00');

    Carbon::setTestNow('2026-04-06 09:00:00');
    $invoice->update(['status' => InvoiceStatusEnum::SENT->value]);
    $invoice->refresh();
    expect($invoice->canceled_at)->toBeNull();
    expect($invoice->sent_at?->toDateTimeString())->toBe('2026-04-01 10:00:00');

    $invoice->update(['status' => InvoiceStatusEnum::DRAFT->value]);
    $invoice->refresh();
    expect($invoice->sent_at)->toBeNull();
    expect($invoice->canceled_at)->toBeNull();

    Carbon::setTestNow();
});

test('invoice created as sent gets sent_at', function () {
    $building = Building::create([
        'name' => 'Invoice Sent At Create Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-INV-SENT-002',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'invoice-sent-at-create-test',
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-INV-SENT-2',
    ]);

    Carbon::setTestNow('2026-05-01 08:00:00');
    $invoice = Invoice::create([
        'operation_id' => $operation->id,
        'status' => InvoiceStatusEnum::SENT->value,
        'code' => 'INV-SAT-002',
        'amount' => 20,
        'description' => 'Created sent',
    ]);

    expect($invoice->fresh()->sent_at?->toDateTimeString())->toBe('2026-05-01 08:00:00');

    Carbon::setTestNow();
});
