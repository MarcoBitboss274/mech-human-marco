<?php

use App\Enums\InvoiceStatusEnum;
use App\Enums\OperationStatusEnum;
use App\Enums\OperationSupplierStatusEnum;
use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\BuildingUserRoleEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\Operation;
use App\Models\Prescription;
use App\Models\Quote;
use App\Models\Supplier;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);
    Role::create(['name' => RoleEnum::CUSTOMER->value, 'guard_name' => 'web']);

    foreach ([
        'operations.index',
        'operations.view-all',
        'operations.prescription.view',
        'operations.supplier.view',
        'operations.quote.view',
        'operations.production.view',
        'operations.invoice.view',
    ] as $permission) {
        Permission::create(['name' => $permission, 'guard_name' => 'web']);
    }
});

function makeAdminForOverview(): User
{
    /** @var User $admin */
    $admin = User::factory()->create();
    $admin->assignRoleToUser(RoleEnum::ADMIN->value);
    $admin->givePermissionTo([
        'operations.index',
        'operations.view-all',
        'operations.prescription.view',
        'operations.supplier.view',
        'operations.quote.view',
        'operations.production.view',
        'operations.invoice.view',
    ]);

    return $admin;
}

function makeCustomerForOverview(Building $building): User
{
    /** @var User $customer */
    $customer = User::factory()->create();
    $customer->makeCustomer();
    $customer->buildings()->attach($building->id, [
        'role' => BuildingUserRoleEnum::MEMBER->value,
    ]);

    return $customer;
}

function buildOverviewScenarioBuilding(string $slug): Building
{
    return Building::create([
        'name' => 'Overview Test Building',
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-OV-' . strtoupper($slug),
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => $slug,
    ]);
}

test('admin show payload include il blocco overview con attori e riepilogo fornitori', function () {
    /** @var \Tests\TestCase $this */
    $agent = User::factory()->create(['name' => 'Marco', 'surname' => 'Agente', 'email' => 'agente@test.it']);
    $requester = User::factory()->create(['name' => 'Giulia', 'surname' => 'Richiedente', 'email' => 'giulia@test.it']);

    $building = buildOverviewScenarioBuilding('admin-overview-building');
    $building->agent_id = $agent->id;
    $building->save();

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::IN_PROGRESS->value,
        'batch_number' => 'BATCH-OV-ADMIN',
    ]);

    Prescription::create([
        'operation_id' => $operation->id,
        'building_id' => $building->id,
        'user_id' => $requester->id,
        'status' => PrescriptionStatusEnum::SENT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'OV-REF',
        'name' => 'Paziente',
        'surname' => 'Uno',
        'send_at' => now(),
    ]);

    $supplier = Supplier::create([
        'name' => 'Fornitore Selezionato',
        'vat' => null,
        'mail' => 'fornitore@test.it',
        'phone' => null,
        'address' => null,
        'cap' => null,
        'city' => null,
        'province' => null,
        'status' => 'active',
    ]);
    $operation->suppliers()->attach($supplier->id, [
        'status' => OperationSupplierStatusEnum::CONTACTED->value,
        'selected' => true,
    ]);

    Quote::create([
        'operation_id' => $operation->id,
        'status' => QuoteStatusEnum::DRAFT->value,
        'notes' => 'draft quote',
    ]);
    Quote::create([
        'operation_id' => $operation->id,
        'status' => QuoteStatusEnum::SENT->value,
        'notes' => 'sent quote',
    ]);

    $this->actingAs(makeAdminForOverview());

    $response = $this->get(route('operations.show', ['operation' => $operation->id]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('overview.actors.requester')
        ->where('overview.actors.requester.email', 'giulia@test.it')
        ->has('overview.actors.building')
        ->where('overview.actors.building.name', 'Overview Test Building')
        ->has('overview.actors.agent')
        ->where('overview.actors.agent.email', 'agente@test.it')
        ->has('overview.actors.supplier')
        ->where('overview.actors.supplier.name', 'Fornitore Selezionato')
        ->where('overview.actors.supplier.email', 'fornitore@test.it')
        ->has('overview.summary.suppliers')
        ->where('overview.summary.suppliers.empty', false)
        ->has('overview.summary.suppliers.selected')
        ->where('overview.summary.suppliers.selected.name', 'Fornitore Selezionato')
        ->where('overview.summary.quotes.count', 2)
        ->where('overview.summary.quotes.empty', false)
        ->where('overview.summary.quotes.rejected_count', 0)
        ->where('overview.summary.quotes.canceled_count', 0)
        ->has('overview.summary.quotes.sent_ids', 1)
        ->where('overview.summary.prescription.empty', false)
        ->has('overview.summary.prescription.sent_at')
        ->where('overview.summary.prescription.confirmed_at', null)
    );
});

test('workspace show payload oscura fornitori e agente per il customer', function () {
    /** @var \Tests\TestCase $this */
    $agent = User::factory()->create(['email' => 'agente@test.it']);
    $building = buildOverviewScenarioBuilding('ws-overview-building');
    $building->agent_id = $agent->id;
    $building->save();

    $customer = makeCustomerForOverview($building);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::IN_PROGRESS->value,
        'batch_number' => 'BATCH-OV-WS',
    ]);

    Prescription::create([
        'operation_id' => $operation->id,
        'building_id' => $building->id,
        'user_id' => $customer->id,
        'status' => PrescriptionStatusEnum::SENT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'WS-REF',
        'name' => 'Paziente',
        'surname' => 'Due',
        'send_at' => now(),
    ]);

    Quote::create([
        'operation_id' => $operation->id,
        'status' => QuoteStatusEnum::DRAFT->value,
        'notes' => 'draft hidden',
    ]);
    Quote::create([
        'operation_id' => $operation->id,
        'status' => QuoteStatusEnum::SENT->value,
        'notes' => 'visible sent',
    ]);

    $this->actingAs($customer);

    $response = $this->get(route('workspace.operations.show', [
        'building' => $building->slug,
        'operation' => $operation->id,
    ]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->has('overview.actors')
        ->where('overview.actors.agent', null)
        ->where('overview.actors.supplier', null)
        ->where('overview.actors.requester.email', $customer->email)
        ->missing('overview.summary.suppliers')
        ->where('overview.summary.quotes.count', 1)
        ->where('overview.summary.quotes.empty', false)
        ->has('overview.summary.quotes.sent_ids', 1)
    );
});

test('customer non vede le fatture non ancora inviate nel riepilogo', function () {
    /** @var \Tests\TestCase $this */
    $building = buildOverviewScenarioBuilding('ws-overview-invoices');
    $customer = makeCustomerForOverview($building);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::IN_PROGRESS->value,
        'batch_number' => 'BATCH-OV-WS-INV',
    ]);

    Prescription::create([
        'operation_id' => $operation->id,
        'building_id' => $building->id,
        'user_id' => $customer->id,
        'status' => PrescriptionStatusEnum::SENT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'WS-INV-REF',
        'name' => 'Paziente',
        'surname' => 'Tre',
        'send_at' => now(),
    ]);

    Invoice::create([
        'operation_id' => $operation->id,
        'status' => InvoiceStatusEnum::DRAFT->value,
        'code' => 'DRAFT-INV',
        'amount' => 10,
        'description' => 'Draft hidden',
    ]);
    Invoice::create([
        'operation_id' => $operation->id,
        'status' => InvoiceStatusEnum::SENT->value,
        'code' => 'SENT-INV',
        'amount' => 20,
        'description' => 'Sent visible',
    ]);

    $this->actingAs($customer);

    $response = $this->get(route('workspace.operations.show', [
        'building' => $building->slug,
        'operation' => $operation->id,
    ]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('overview.summary.invoices.empty', false)
        ->has('overview.summary.invoices.sent_ids', 1)
        ->where('overview.summary.invoices.canceled_count', 0)
    );
});

test('admin overview riporta counter dei preventivi rifiutati e annullati separati', function () {
    $building = buildOverviewScenarioBuilding('admin-overview-quote-counters');
    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::IN_PROGRESS->value,
        'batch_number' => 'BATCH-OV-QC',
    ]);

    Quote::create(['operation_id' => $operation->id, 'status' => QuoteStatusEnum::REJECTED->value, 'notes' => 'r1']);
    Quote::create(['operation_id' => $operation->id, 'status' => QuoteStatusEnum::REJECTED->value, 'notes' => 'r2']);
    Quote::create(['operation_id' => $operation->id, 'status' => QuoteStatusEnum::CANCELED->value, 'notes' => 'c1']);
    $accepted = Quote::create(['operation_id' => $operation->id, 'status' => QuoteStatusEnum::ACCEPTED->value, 'notes' => 'acc', 'accepted_at' => now()]);

    $this->actingAs(makeAdminForOverview());

    $response = $this->get(route('operations.show', ['operation' => $operation->id]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('overview.summary.quotes.rejected_count', 2)
        ->where('overview.summary.quotes.canceled_count', 1)
        ->where('overview.summary.quotes.accepted.id', $accepted->id)
        ->has('overview.summary.quotes.sent_ids', 0)
    );
});

test('customer vede fatture annullate solo se erano gia state inviate', function () {
    $building = buildOverviewScenarioBuilding('ws-overview-canceled-invoices');
    $customer = makeCustomerForOverview($building);

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::IN_PROGRESS->value,
        'batch_number' => 'BATCH-OV-WS-CAN',
    ]);

    Prescription::create([
        'operation_id' => $operation->id,
        'building_id' => $building->id,
        'user_id' => $customer->id,
        'status' => PrescriptionStatusEnum::SENT->value,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'WS-CAN-REF',
        'name' => 'X',
        'surname' => 'Y',
        'send_at' => now(),
    ]);

    $sentThenCanceled = Invoice::create([
        'operation_id' => $operation->id,
        'status' => InvoiceStatusEnum::SENT->value,
        'code' => 'CAN-SENT',
        'amount' => 10,
        'description' => 'sent then canceled',
    ]);
    $sentThenCanceled->update(['status' => InvoiceStatusEnum::CANCELED->value]);

    Invoice::create([
        'operation_id' => $operation->id,
        'status' => InvoiceStatusEnum::CANCELED->value,
        'code' => 'CAN-DRAFT',
        'amount' => 5,
        'description' => 'canceled while draft',
    ]);

    $this->actingAs($customer);

    $response = $this->get(route('workspace.operations.show', [
        'building' => $building->slug,
        'operation' => $operation->id,
    ]));

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->where('overview.summary.invoices.canceled_count', 1)
        ->has('overview.summary.invoices.sent_ids', 0)
    );
});
