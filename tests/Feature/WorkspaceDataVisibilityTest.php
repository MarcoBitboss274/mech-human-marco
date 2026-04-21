<?php

use App\Enums\BuildingUserRoleEnum;
use App\Enums\InvoiceStatusEnum;
use App\Enums\OperationStatusEnum;
use App\Enums\OrderStatusEnum;
use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Operation;
use App\Models\Order;
use App\Models\Prescription;
use App\Models\Quote;
use App\Models\User;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => RoleEnum::CUSTOMER->value, 'guard_name' => 'web']);
});

test('member without workspace.operations.view-all sees only own operations in workspace index', function () {
    /** @var \Tests\TestCase $this */
    $building = Building::create([
        'name' => 'WS Visibility Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-WS-VIS-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'ws-visibility-test',
    ]);

    /** @var User $member */
    $member = User::factory()->create();
    $member->makeCustomer();
    $member->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::MEMBER->value,
    ]);

    $otherRequester = User::factory()->create();

    $ownOperation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-OWN',
    ]);

    Prescription::create([
        'operation_id' => $ownOperation->id,
        'building_id' => $building->id,
        'user_id' => $member->id,
        'status' => PrescriptionStatusEnum::DRAFT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'OWN-REF',
        'name' => 'Mario',
        'surname' => 'Rossi',
    ]);

    $otherOperation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-OTHER',
    ]);

    Prescription::create([
        'operation_id' => $otherOperation->id,
        'building_id' => $building->id,
        'user_id' => $otherRequester->id,
        'status' => PrescriptionStatusEnum::DRAFT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'OTHER-REF',
        'name' => 'Luigi',
        'surname' => 'Bianchi',
    ]);

    $this->actingAs($member);

    $response = $this->get(route('workspace.operations.index', [
        'building' => $building->slug,
    ]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('operations.data', 1)
        ->where('operations.data.0.id', $ownOperation->id)
    );
});

test('member without workspace.operations.view-all sees only own prescriptions/quotes/orders/invoices in workspace indexes', function () {
    /** @var \Tests\TestCase $this */
    $building = Building::create([
        'name' => 'WS Visibility Test 2',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-WS-VIS-002',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'ws-visibility-test-2',
    ]);

    /** @var User $member */
    $member = User::factory()->create();
    $member->makeCustomer();
    $member->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::MEMBER->value,
    ]);

    $otherRequester = User::factory()->create();

    $ownOperation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-OWN-2',
    ]);

    $ownPrescription = Prescription::create([
        'operation_id' => $ownOperation->id,
        'building_id' => $building->id,
        'user_id' => $member->id,
        'status' => PrescriptionStatusEnum::DRAFT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'OWN-REF-2',
        'name' => 'Mario',
        'surname' => 'Rossi',
    ]);

    $otherOperation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-OTHER-2',
    ]);

    $otherPrescription = Prescription::create([
        'operation_id' => $otherOperation->id,
        'building_id' => $building->id,
        'user_id' => $otherRequester->id,
        'status' => PrescriptionStatusEnum::DRAFT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'OTHER-REF-2',
        'name' => 'Luigi',
        'surname' => 'Bianchi',
    ]);

    $ownQuote = Quote::create([
        'operation_id' => $ownOperation->id,
        'status' => QuoteStatusEnum::SENT->value,
        'notes' => 'own quote',
    ]);
    $otherQuote = Quote::create([
        'operation_id' => $otherOperation->id,
        'status' => QuoteStatusEnum::SENT->value,
        'notes' => 'other quote',
    ]);

    $ownOrder = Order::create([
        'operation_id' => $ownOperation->id,
        'status' => OrderStatusEnum::CONFIRMED->value,
        'code' => 'OWN-ORDER',
        'description' => null,
    ]);
    $otherOrder = Order::create([
        'operation_id' => $otherOperation->id,
        'status' => OrderStatusEnum::CONFIRMED->value,
        'code' => 'OTHER-ORDER',
        'description' => null,
    ]);

    $ownInvoice = Invoice::create([
        'operation_id' => $ownOperation->id,
        'status' => InvoiceStatusEnum::SENT->value,
        'code' => 'OWN-INVOICE',
        'description' => null,
    ]);
    $otherInvoice = Invoice::create([
        'operation_id' => $otherOperation->id,
        'status' => InvoiceStatusEnum::SENT->value,
        'code' => 'OTHER-INVOICE',
        'description' => null,
    ]);

    $this->actingAs($member);

    $prescriptionsResponse = $this->get(route('workspace.prescriptions.index', ['building' => $building->slug]));
    $prescriptionsResponse->assertStatus(200);
    $prescriptionsResponse->assertInertia(fn ($page) => $page
        ->has('prescriptions.data', 1)
        ->where('prescriptions.data.0.id', $ownPrescription->id)
    );

    $quotesResponse = $this->get(route('workspace.quotes.index', ['building' => $building->slug]));
    $quotesResponse->assertStatus(200);
    $quotesResponse->assertInertia(fn ($page) => $page
        ->has('quotes.data', 1)
        ->where('quotes.data.0.id', $ownQuote->id)
    );

    $ordersResponse = $this->get(route('workspace.orders.index', ['building' => $building->slug]));
    $ordersResponse->assertStatus(200);
    $ordersResponse->assertInertia(fn ($page) => $page
        ->has('orders.data', 1)
        ->where('orders.data.0.id', $ownOrder->id)
    );

    $invoicesResponse = $this->get(route('workspace.invoices.index', ['building' => $building->slug]));
    $invoicesResponse->assertStatus(200);
    $invoicesResponse->assertInertia(fn ($page) => $page
        ->has('invoices.data', 1)
        ->where('invoices.data.0.id', $ownInvoice->id)
    );

    expect($otherPrescription)->not->toBeNull();
    expect($otherQuote)->not->toBeNull();
    expect($otherOrder)->not->toBeNull();
    expect($otherInvoice)->not->toBeNull();
});

test('member without workspace.operations.view-all cannot access show pages for non-requested records', function () {
    /** @var \Tests\TestCase $this */
    $building = Building::create([
        'name' => 'WS Show Visibility Test',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-WS-VIS-SHOW-001',
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'ws-show-visibility-test',
    ]);

    /** @var User $member */
    $member = User::factory()->create();
    $member->makeCustomer();
    $member->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::MEMBER->value,
    ]);

    $otherRequester = User::factory()->create();

    $ownOperation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-OWN-SHOW',
    ]);
    $ownPrescription = Prescription::create([
        'operation_id' => $ownOperation->id,
        'building_id' => $building->id,
        'user_id' => $member->id,
        'status' => PrescriptionStatusEnum::DRAFT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'OWN-SHOW-REF',
        'name' => 'Mario',
        'surname' => 'Rossi',
    ]);
    $ownQuote = Quote::create([
        'operation_id' => $ownOperation->id,
        'status' => QuoteStatusEnum::SENT->value,
        'notes' => 'own show quote',
    ]);
    $ownOrder = Order::create([
        'operation_id' => $ownOperation->id,
        'status' => OrderStatusEnum::CONFIRMED->value,
        'code' => 'OWN-SHOW-ORDER',
        'description' => null,
    ]);
    $ownInvoice = Invoice::create([
        'operation_id' => $ownOperation->id,
        'status' => InvoiceStatusEnum::SENT->value,
        'code' => 'OWN-SHOW-INVOICE',
        'description' => null,
    ]);

    $otherOperation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::DRAFT->value,
        'batch_number' => 'BATCH-OTHER-SHOW',
    ]);
    $otherPrescription = Prescription::create([
        'operation_id' => $otherOperation->id,
        'building_id' => $building->id,
        'user_id' => $otherRequester->id,
        'status' => PrescriptionStatusEnum::DRAFT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'OTHER-SHOW-REF',
        'name' => 'Luigi',
        'surname' => 'Bianchi',
    ]);
    $otherQuote = Quote::create([
        'operation_id' => $otherOperation->id,
        'status' => QuoteStatusEnum::SENT->value,
        'notes' => 'other show quote',
    ]);
    $otherOrder = Order::create([
        'operation_id' => $otherOperation->id,
        'status' => OrderStatusEnum::CONFIRMED->value,
        'code' => 'OTHER-SHOW-ORDER',
        'description' => null,
    ]);
    $otherInvoice = Invoice::create([
        'operation_id' => $otherOperation->id,
        'status' => InvoiceStatusEnum::SENT->value,
        'code' => 'OTHER-SHOW-INVOICE',
        'description' => null,
    ]);

    $this->actingAs($member);

    $this->get(route('workspace.operations.show', ['building' => $building->slug, 'operation' => $ownOperation->id]))->assertStatus(200);
    $this->get(route('workspace.prescriptions.show', ['building' => $building->slug, 'prescription' => $ownPrescription->id]))->assertStatus(200);
    $this->get(route('workspace.quotes.show', ['building' => $building->slug, 'quote' => $ownQuote->id]))->assertStatus(200);
    $this->get(route('workspace.orders.show', ['building' => $building->slug, 'order' => $ownOrder->id]))->assertStatus(200);
    $this->get(route('workspace.invoices.show', ['building' => $building->slug, 'invoice' => $ownInvoice->id]))->assertStatus(200);

    $this->get(route('workspace.operations.show', ['building' => $building->slug, 'operation' => $otherOperation->id]))->assertStatus(404);
    $this->get(route('workspace.prescriptions.show', ['building' => $building->slug, 'prescription' => $otherPrescription->id]))->assertStatus(404);
    $this->get(route('workspace.quotes.show', ['building' => $building->slug, 'quote' => $otherQuote->id]))->assertStatus(404);
    $this->get(route('workspace.orders.show', ['building' => $building->slug, 'order' => $otherOrder->id]))->assertStatus(404);
    $this->get(route('workspace.invoices.show', ['building' => $building->slug, 'invoice' => $otherInvoice->id]))->assertStatus(404);
});

