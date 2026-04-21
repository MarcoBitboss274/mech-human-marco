<?php

use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\QuoteStatusEnum;
use App\Enums\RoleEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Prescription;
use App\Models\Quote;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $adminRole = Role::create(['name' => RoleEnum::ADMIN->value, 'guard_name' => 'web']);
    $quotesEditPermission = Permission::create(['name' => 'quotes.edit', 'guard_name' => 'web']);
    $adminRole->givePermissionTo($quotesEditPermission);

    $this->adminUser = User::factory()->create();
    $this->adminUser->assignRoleToUser($adminRole->name);

    $this->building = Building::create([
        'name' => 'Operations Test Building',
        'vat' => '12345678901',
        'is_studio' => true,
        'customer_code' => 'CUS-OPS-001',
        'is_laboratory' => false,
        'headquarter_address' => 'Via Test 1',
        'legal_address' => 'Via Test 1',
        'approved' => true,
    ]);
});

function wizardPayload(int $buildingId, bool $draft): array
{
    return [
        'draft' => $draft,
        'building_id' => $buildingId,
        'user_id' => null,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'REF-OPS-001',
        'name' => 'Mario',
        'surname' => 'Rossi',
        'age' => 35,
        'gender' => null,
        'lybra_aligner_details' => [
            'cut_line' => 'line_a',
            'note' => 'Test note',
        ],
    ];
}

function sentQuotePayload(int $buildingId): Quote
{
    $operation = Operation::create([
        'building_id' => $buildingId,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::WAITING_APPROVAL->value,
    ]);

    return Quote::create([
        'operation_id' => $operation->id,
        'status' => QuoteStatusEnum::SENT->value,
        'notes' => 'Original quote note',
    ]);
}

test('store wizard sets draft statuses when draft flag is true', function () {
    $this->actingAs($this->adminUser);

    $response = $this->post(route('operations.store-wizard'), wizardPayload($this->building->id, true));

    $operation = Operation::query()->latest('id')->first();
    $prescription = Prescription::query()
        ->where('operation_id', $operation?->id)
        ->latest('id')
        ->first();

    $response->assertRedirect(route('operations.show', ['operation' => $operation?->id]));
    expect($operation)->not->toBeNull();
    expect($prescription)->not->toBeNull();
    expect($operation->status)->toBe(OperationStatusEnum::DRAFT->value);
    expect($prescription->status)->toBe(PrescriptionStatusEnum::DRAFT->value);
});

test('store wizard sets requested and sent statuses when draft flag is false', function () {
    $this->actingAs($this->adminUser);

    $response = $this->post(route('operations.store-wizard'), wizardPayload($this->building->id, false));

    $operation = Operation::query()->latest('id')->first();
    $prescription = Prescription::query()
        ->where('operation_id', $operation?->id)
        ->latest('id')
        ->first();

    $response->assertRedirect(route('operations.show', ['operation' => $operation?->id]));
    expect($operation)->not->toBeNull();
    expect($prescription)->not->toBeNull();
    expect($operation->status)->toBe(OperationStatusEnum::REQUESTED->value);
    expect($prescription->status)->toBe(PrescriptionStatusEnum::SENT->value);
});

test('accept quote from sent sets accepted status and datetime', function () {
    $this->actingAs($this->adminUser);
    $quote = sentQuotePayload($this->building->id);

    $response = $this->post(route('quotes.accept', ['quote' => $quote->id]));

    $response->assertStatus(302);
    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatusEnum::ACCEPTED->value);
    expect($quote->accepted_at)->not->toBeNull();
    expect($quote->rejected_at)->toBeNull();
    expect($quote->canceled_at)->toBeNull();
});

test('reject quote from sent requires notes and stores rejected metadata', function () {
    $this->actingAs($this->adminUser);
    $quote = sentQuotePayload($this->building->id);

    $invalidResponse = $this->from(route('operations.show', ['operation' => $quote->operation_id]))
        ->post(route('quotes.reject', ['quote' => $quote->id]), [
            'notes' => '   ',
        ]);

    $invalidResponse->assertRedirect(route('operations.show', ['operation' => $quote->operation_id]));
    $invalidResponse->assertSessionHasErrors('notes');

    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatusEnum::SENT->value);

    $response = $this->post(route('quotes.reject', ['quote' => $quote->id]), [
        'notes' => 'Rejected because budget is too high',
    ]);

    $response->assertStatus(302);
    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatusEnum::REJECTED->value);
    expect($quote->rejected_at)->not->toBeNull();
    expect($quote->rejected_notes)->toBe('Rejected because budget is too high');
    expect($quote->accepted_at)->toBeNull();
    expect($quote->canceled_at)->toBeNull();
});

test('cancel quote from sent requires notes and stores canceled metadata', function () {
    $this->actingAs($this->adminUser);
    $quote = sentQuotePayload($this->building->id);

    $invalidResponse = $this->from(route('operations.show', ['operation' => $quote->operation_id]))
        ->post(route('quotes.cancel', ['quote' => $quote->id]), [
            'notes' => '',
        ]);

    $invalidResponse->assertRedirect(route('operations.show', ['operation' => $quote->operation_id]));
    $invalidResponse->assertSessionHasErrors('notes');

    $quote->refresh();
    expect($quote->status)->toBe(QuoteStatusEnum::SENT->value);

    $response = $this->post(route('quotes.cancel', ['quote' => $quote->id]), [
        'notes' => 'Canceled by customer request',
    ]);

    $response->assertStatus(302);
    $quote->refresh();

    expect($quote->status)->toBe(QuoteStatusEnum::CANCELED->value);
    expect($quote->canceled_at)->not->toBeNull();
    expect($quote->canceled_notes)->toBe('Canceled by customer request');
    expect($quote->accepted_at)->toBeNull();
    expect($quote->rejected_at)->toBeNull();
});

test('quote transition endpoints are blocked when status is not sent', function () {
    $this->actingAs($this->adminUser);

    $operation = Operation::create([
        'building_id' => $this->building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => OperationStatusEnum::WAITING_APPROVAL->value,
    ]);

    $quote = Quote::create([
        'operation_id' => $operation->id,
        'status' => QuoteStatusEnum::DRAFT->value,
        'notes' => 'Draft quote',
    ]);

    $acceptResponse = $this->post(route('quotes.accept', ['quote' => $quote->id]));
    $acceptResponse->assertSessionHasErrors('status');

    $rejectResponse = $this->post(route('quotes.reject', ['quote' => $quote->id]), [
        'notes' => 'Cannot reject draft',
    ]);
    $rejectResponse->assertSessionHasErrors('status');

    $cancelResponse = $this->post(route('quotes.cancel', ['quote' => $quote->id]), [
        'notes' => 'Cannot cancel draft',
    ]);
    $cancelResponse->assertSessionHasErrors('status');
});
