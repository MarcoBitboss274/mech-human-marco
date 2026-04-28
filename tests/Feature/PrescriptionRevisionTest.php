<?php

use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Models\Building;
use App\Models\Operation;
use App\Models\Prescription;
use App\Models\PrescriptionLybraAligner;
use App\Models\Revision;
use App\Models\User;
use App\Notifications\User\ClosedPrescriptionRevisionForUser;
use App\Notifications\User\RequestPrescriptionRevisionForUser;
use App\Services\PrescriptionService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

function buildRevisionPrescription(string $slug, string $status = 'confirmed'): Prescription
{
    $building = Building::create([
        'name' => 'Revision Test ' . $slug,
        'vat' => null,
        'is_studio' => null,
        'customer_code' => 'CUS-REV-' . strtoupper($slug),
        'is_laboratory' => null,
        'headquarter_address' => null,
        'legal_address' => null,
        'approved' => true,
        'fiscal_code' => null,
        'sdi_code' => null,
        'slug' => 'revision-' . $slug,
    ]);

    $user = User::factory()->create();

    $operation = Operation::create([
        'building_id' => $building->id,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'status' => 'draft',
        'batch_number' => 'BATCH-REV-' . strtoupper($slug),
    ]);

    $prescription = Prescription::create([
        'operation_id' => $operation->id,
        'building_id' => $building->id,
        'user_id' => $user->id,
        'status' => $status,
        'typology' => PrescriptionTypologyEnum::LYBRA_ALIGNER->value,
        'ref' => 'REV-' . strtoupper($slug),
        'name' => 'Test',
        'surname' => 'Patient',
        'age' => 40,
        'gender' => 'M',
        'manual' => true,
        'send_at' => now()->subDay(),
        'expire_at' => now()->subDay()->addMonths(6),
    ]);

    PrescriptionLybraAligner::create([
        'prescription_id' => $prescription->id,
        'cut_line' => 'standard',
    ]);

    return $prescription;
}

test('opening a revision on a DRAFT prescription is blocked', function () {
    $prescription = buildRevisionPrescription('draft-block', 'draft');
    $admin = User::factory()->create();

    expect(fn() => PrescriptionService::openRevision($prescription, 'Motivo test', $admin))
        ->toThrow(ValidationException::class);

    expect($prescription->fresh()->activeRevision()->exists())->toBeFalse();
});

test('opening a revision on SENT prescription does not change status and creates revision + reason', function () {
    Notification::fake();
    $prescription = buildRevisionPrescription('open-sent', PrescriptionStatusEnum::SENT->value);
    $admin = User::factory()->create();

    PrescriptionService::openRevision($prescription, 'Manca documento X', $admin);

    $fresh = $prescription->fresh();
    expect($fresh->status)->toBe(PrescriptionStatusEnum::SENT->value);

    $revision = $fresh->activeRevision()->with('reasons')->first();
    expect($revision)->not->toBeNull();
    expect($revision->opened_by)->toBe($admin->id);
    expect($revision->reasons)->toHaveCount(1);
    expect($revision->reasons[0]->content)->toBe('Manca documento X');
});

test('cannot open a second revision while one is open', function () {
    $prescription = buildRevisionPrescription('double-open', PrescriptionStatusEnum::CONFIRMED->value);
    $admin = User::factory()->create();

    PrescriptionService::openRevision($prescription, 'Primo motivo di revisione', $admin);

    expect(fn() => PrescriptionService::openRevision($prescription->fresh(), 'Secondo motivo', $admin))
        ->toThrow(ValidationException::class);

    expect($prescription->fresh()->revisions()->count())->toBe(1);
});

test('adding a reason appends a revision_reason to the open revision', function () {
    $prescription = buildRevisionPrescription('add-reason', PrescriptionStatusEnum::CONFIRMED->value);
    $admin = User::factory()->create();

    PrescriptionService::openRevision($prescription, 'Primo motivo', $admin);
    PrescriptionService::addRevisionReason($prescription->fresh(), 'Secondo motivo', $admin);

    $revision = $prescription->fresh()->activeRevision()->with('reasons')->first();
    expect($revision->reasons)->toHaveCount(2);
    expect($revision->reasons->pluck('content')->all())->toBe(['Primo motivo', 'Secondo motivo']);
});

test('resubmit updates last_submitted_at on the open revision and does not change status', function () {
    Notification::fake();
    $prescription = buildRevisionPrescription('resubmit', PrescriptionStatusEnum::CONFIRMED->value);
    $admin = User::factory()->create();
    $customer = $prescription->user;

    PrescriptionService::openRevision($prescription, 'Motivo primo', $admin);

    // Simulo invio customer passando dal service
    PrescriptionService::sendPrescription($prescription->fresh(), $customer);

    $revision = $prescription->fresh()->activeRevision()->first();
    expect($revision->last_submitted_at)->not->toBeNull();
    expect($prescription->fresh()->status)->toBe(PrescriptionStatusEnum::CONFIRMED->value);

    $firstSubmit = $revision->last_submitted_at;
    sleep(1);

    PrescriptionService::sendPrescription($prescription->fresh(), $customer);
    $revision = $prescription->fresh()->activeRevision()->first();
    expect($revision->last_submitted_at->greaterThan($firstSubmit))->toBeTrue();
});

test('customer cannot send on SENT prescription without an active revision', function () {
    $prescription = buildRevisionPrescription('no-send', PrescriptionStatusEnum::SENT->value);

    expect(fn() => PrescriptionService::sendPrescription($prescription, $prescription->user))
        ->toThrow(ValidationException::class);
});

test('closing a revision sets timestamps and notifies the customer', function () {
    Notification::fake();
    $prescription = buildRevisionPrescription('close', PrescriptionStatusEnum::CONFIRMED->value);
    $admin = User::factory()->create();

    PrescriptionService::openRevision($prescription, 'Motivo close', $admin);
    PrescriptionService::closeRevision($prescription->fresh(), $admin);

    $fresh = $prescription->fresh();
    expect($fresh->status)->toBe(PrescriptionStatusEnum::CONFIRMED->value);
    expect($fresh->activeRevision()->exists())->toBeFalse();

    $revision = $fresh->revisions()->first();
    expect($revision->closed_at)->not->toBeNull();
    expect($revision->closed_by)->toBe($admin->id);

    Notification::assertSentTo($prescription->user, ClosedPrescriptionRevisionForUser::class);
});

test('closing a revision without any customer submission is allowed', function () {
    $prescription = buildRevisionPrescription('close-no-submit', PrescriptionStatusEnum::SENT->value);
    $admin = User::factory()->create();

    PrescriptionService::openRevision($prescription, 'Aperta per errore ops', $admin);
    PrescriptionService::closeRevision($prescription->fresh(), $admin);

    $revision = $prescription->fresh()->revisions()->first();
    expect($revision->closed_at)->not->toBeNull();
    expect($revision->last_submitted_at)->toBeNull();
});

test('after closing a revision a new one can be opened', function () {
    $prescription = buildRevisionPrescription('reopen', PrescriptionStatusEnum::CONFIRMED->value);
    $admin = User::factory()->create();

    PrescriptionService::openRevision($prescription, 'Prima revisione', $admin);
    PrescriptionService::closeRevision($prescription->fresh(), $admin);
    PrescriptionService::openRevision($prescription->fresh(), 'Seconda revisione', $admin);

    expect($prescription->fresh()->revisions()->count())->toBe(2);
    expect($prescription->fresh()->activeRevision()->exists())->toBeTrue();
});

test('opening a revision sends the request notification to the customer', function () {
    Notification::fake();
    $prescription = buildRevisionPrescription('notify-open', PrescriptionStatusEnum::CONFIRMED->value);
    $admin = User::factory()->create();

    PrescriptionService::openRevision($prescription, 'Aggiungere documentazione', $admin);

    Notification::assertSentTo($prescription->user, RequestPrescriptionRevisionForUser::class);
});

test('adding a reason notifies the customer again', function () {
    Notification::fake();
    $prescription = buildRevisionPrescription('notify-add', PrescriptionStatusEnum::CONFIRMED->value);
    $admin = User::factory()->create();

    PrescriptionService::openRevision($prescription, 'Motivo uno', $admin);
    PrescriptionService::addRevisionReason($prescription->fresh(), 'Motivo due', $admin);

    Notification::assertSentToTimes($prescription->user, RequestPrescriptionRevisionForUser::class, 2);
});

test('resubmit throws if no active revision exists', function () {
    $prescription = buildRevisionPrescription('resubmit-stale', PrescriptionStatusEnum::CONFIRMED->value);
    $admin = User::factory()->create();

    $revision = Revision::create([
        'prescription_id' => $prescription->id,
        'opened_at' => now()->subHour(),
        'opened_by' => $admin->id,
        'closed_at' => now(),
        'closed_by' => $admin->id,
    ]);

    expect(fn() => PrescriptionService::sendPrescription($prescription->fresh(), $prescription->user))
        ->toThrow(ValidationException::class);
});
