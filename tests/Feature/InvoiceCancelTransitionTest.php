<?php

use App\Enums\InvoiceStatusEnum;
use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\RoleEnum;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Operation;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::create(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);
    Role::create(['name' => RoleEnum::CUSTOMER->value, 'guard_name' => 'web']);

    foreach ([
        'operations.index',
        'operations.view-all',
        'operations.invoice.view',
        'operations.invoice.manage',
    ] as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }
});

function buildInvoiceCancelScenario(string $slug): Invoice
{
    $building = Building::create([
        'name' => 'Invoice Cancel Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-INV-CAN-T-' . strtoupper($slug),
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'invoice-cancel-' . $slug,
    ]);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::IN_PROGRESS->value,
        'batch_number' => 'BATCH-INV-CAN-T-' . strtoupper($slug),
    ]);

    return Invoice::create([
        'operation_id' => $operation->id,
        'status' => InvoiceStatusEnum::SENT->value,
        'code' => 'INV-CAN-T-' . strtoupper($slug),
        'amount' => 30,
        'description' => 'To cancel',
    ]);
}

test('admin can transition invoice from sent to canceled and canceled_at is synced', function () {
    $invoice = buildInvoiceCancelScenario('a');

    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRoleToUser(RoleEnum::ADMIN->value);
    $admin->givePermissionTo([
        'operations.index',
        'operations.view-all',
        'operations.invoice.view',
        'operations.invoice.manage',
    ]);

    $this->actingAs($admin);

    $response = $this->patch(
        route('operations.invoices.status', [
            'operation' => $invoice->operation_id,
            'invoice' => $invoice->id,
        ]),
        ['status' => InvoiceStatusEnum::CANCELED->value],
    );

    $response->assertRedirect();
    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatusEnum::CANCELED->value);
    expect($invoice->canceled_at)->not->toBeNull();
    expect($invoice->sent_at)->not->toBeNull();
});

test('customer cannot transition invoice status', function () {
    $invoice = buildInvoiceCancelScenario('b');

    /** @var User $customer */
    $customer = User::factory()->create();
    $customer->makeCustomer();

    $this->actingAs($customer);

    $response = $this->patch(
        route('operations.invoices.status', [
            'operation' => $invoice->operation_id,
            'invoice' => $invoice->id,
        ]),
        ['status' => InvoiceStatusEnum::CANCELED->value],
    );

    expect($response->status())->toBeIn([403, 404]);
    expect($invoice->fresh()->status)->toBe(InvoiceStatusEnum::SENT->value);
});
