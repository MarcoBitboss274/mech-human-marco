<?php

use App\Enums\OperationStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Order;
use Carbon\Carbon;

test('order confirmed_at is set on first transition to confirmed and cleared when leaving confirmed', function () {
    $building = Building::create([
        'name' => 'Order Confirmed At Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-ORD-CONF-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'order-confirmed-at-test',
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-ORD-CONF',
    ]);

    $order = Order::create([
        'operation_id' => $operation->id,
        'status' => OrderStatusEnum::PENDING->value,
        'code' => 'ORD-CAT-001',
        'amount' => 10,
        'description' => 'Test',
    ]);
    expect($order->fresh()->confirmed_at)->toBeNull();

    Carbon::setTestNow('2026-04-01 10:00:00');
    $order->update(['status' => OrderStatusEnum::CONFIRMED->value]);
    $order->refresh();
    expect($order->confirmed_at?->toDateTimeString())->toBe('2026-04-01 10:00:00');

    Carbon::setTestNow('2026-04-02 15:00:00');
    $order->update(['description' => 'Updated only']);
    $order->refresh();
    expect($order->confirmed_at?->toDateTimeString())->toBe('2026-04-01 10:00:00');

    $order->update(['status' => OrderStatusEnum::PENDING->value]);
    $order->refresh();
    expect($order->confirmed_at)->toBeNull();

    Carbon::setTestNow();
});

test('order created as confirmed gets confirmed_at', function () {
    $building = Building::create([
        'name' => 'Order Confirmed At Create Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-ORD-CONF-002',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'order-confirmed-at-create-test',
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-ORD-CONF-2',
    ]);

    Carbon::setTestNow('2026-05-01 08:00:00');
    $order = Order::create([
        'operation_id' => $operation->id,
        'status' => OrderStatusEnum::CONFIRMED->value,
        'code' => 'ORD-CAT-002',
        'amount' => 20,
        'description' => 'Created confirmed',
    ]);

    expect($order->fresh()->confirmed_at?->toDateTimeString())->toBe('2026-05-01 08:00:00');

    Carbon::setTestNow();
});
