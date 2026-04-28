<?php

namespace App\Services;

use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Models\Operation;
use App\Models\Prescription;
use App\Models\Revision;
use App\Models\User;
use App\Notifications\Admin\ResubmittedPrescriptionForAdmin;
use App\Notifications\Admin\SendPrescriptionForAdmin;
use App\Notifications\Agent\SendPrescriptionForAgent;
use App\Notifications\User\ClosedPrescriptionRevisionForUser;
use App\Notifications\User\RequestPrescriptionRevisionForUser;
use App\Notifications\User\SendPrescriptionForUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrescriptionService extends ModelService
{
    /**
     * Get the class of the model
     */
    protected static function getClass(): string
    {
        return Prescription::class;
    }

    /**
     * Fetch model
     */
    public static function fetch(Request|null $request)
    {
        return static::getClass()::query()->with([
            'operation',
            'building:id,name',
            'user:id,name,surname',
        ]);
    }

    /**
     * Apply search
     */
    public static function applySearch(Builder $query, Request|null $request): Builder
    {
        if ($request['query'] ?? false) {
            QueryService::querySearch($query, $request['query'], ['name', 'surname', 'ref', 'typology']);
        }

        return $query;
    }

    /**
     * Apply sorts
     */
    public static function applySorts(Builder $query, Request|null $request): Builder
    {
        return $query->latest();
    }

    /**
     * Get payload for admin prescription show page
     */
    public static function getAdminShowData(Prescription $prescription): array
    {
        $prescription->load([
            'operation',
            'building:id,name',
            'user:id,name,surname',
            'protrusorDetails',
            'lybraAlignerDetails',
            'activeRevision.reasons',
        ]);

        return [
            'prescription' => $prescription->toArray(),
        ];
    }

    /**
     * Initial send (DRAFT -> SENT) or resubmit within an open revision.
     */
    public static function sendPrescription(Prescription $prescription, ?User $causer = null): void
    {
        if (! static::validatePrescription($prescription)) {
            throw ValidationException::withMessages([
                'prescription' => 'Prescrizione non valida, verificare tutti i campi',
            ]);
        }

        $prescription->loadMissing('activeRevision');

        if ($prescription->activeRevision !== null) {
            static::resubmitRevision($prescription, $causer);

            return;
        }

        if ($prescription->status !== PrescriptionStatusEnum::DRAFT->value) {
            throw ValidationException::withMessages([
                'prescription' => 'Stato della prescrizione non valido per l\'invio',
            ]);
        }

        $sendAt = now();

        $prescription->update([
            'status' => PrescriptionStatusEnum::SENT->value,
            'send_at' => $sendAt,
            'expire_at' => $sendAt->copy()->addMonths(6),
        ]);

        OperationService::updateStatus($prescription->operation, OperationStatusEnum::REQUESTED->value);

        NotificationService::sendToAdmins(new SendPrescriptionForAdmin($prescription));
        NotificationService::sendToUser($prescription->user, new SendPrescriptionForUser($prescription));
        NotificationService::sendToUser($prescription->building?->agent, new SendPrescriptionForAgent($prescription));
    }

    /**
     * Mark prescription as confirmed (only from SENT).
     */
    public static function confirmPrescription(Prescription $prescription, ?User $causer = null): void
    {
        if ($prescription->status !== PrescriptionStatusEnum::SENT->value) {
            throw ValidationException::withMessages([
                'prescription' => 'Impossibile confermare: la prescrizione non è in uno stato conferma-abile',
            ]);
        }

        $prescription->update([
            'status' => PrescriptionStatusEnum::CONFIRMED->value,
        ]);

        OperationService::updateStatus($prescription->operation, OperationStatusEnum::IN_PROGRESS->value);

        static::logOnOperation(
            $prescription,
            'prescription_confirmed',
            ['prescription_id' => $prescription->id],
            static::causerName($causer) . ' ha confermato la prescrizione',
            $causer,
        );
    }

    /**
     * Open a new revision on a prescription that is SENT or CONFIRMED.
     */
    public static function openRevision(Prescription $prescription, string $reason, ?User $causer = null): Revision
    {
        if ($prescription->status === PrescriptionStatusEnum::DRAFT->value) {
            throw ValidationException::withMessages([
                'prescription' => 'Non è possibile aprire una revisione su una prescrizione in bozza',
            ]);
        }

        return DB::transaction(function () use ($prescription, $reason, $causer) {
            $locked = Prescription::query()
                ->whereKey($prescription->id)
                ->lockForUpdate()
                ->first();

            $existing = Revision::query()
                ->where('prescription_id', $prescription->id)
                ->whereNull('closed_at')
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'prescription' => 'Esiste già una revisione aperta per questa prescrizione',
                ]);
            }

            $revision = Revision::create([
                'prescription_id' => $prescription->id,
                'opened_at' => now(),
                'opened_by' => $causer?->id,
            ]);

            $revision->reasons()->create([
                'content' => $reason,
                'created_by' => $causer?->id,
            ]);

            static::logOnOperation(
                $prescription,
                'prescription_revision_opened',
                [
                    'prescription_id' => $prescription->id,
                    'revision_id' => $revision->id,
                    'reason' => $reason,
                ],
                static::causerName($causer) . ' ha aperto una revisione: ' . $reason,
                $causer,
            );

            NotificationService::sendToUser(
                $prescription->user,
                new RequestPrescriptionRevisionForUser($prescription, $reason),
            );

            return $revision->refresh();
        });
    }

    /**
     * Add a further reason to the currently open revision.
     */
    public static function addRevisionReason(Prescription $prescription, string $reason, ?User $causer = null): void
    {
        $revision = $prescription->activeRevision()->first();

        if ($revision === null) {
            throw ValidationException::withMessages([
                'prescription' => 'Non esiste una revisione aperta per questa prescrizione',
            ]);
        }

        $revision->reasons()->create([
            'content' => $reason,
            'created_by' => $causer?->id,
        ]);

        static::logOnOperation(
            $prescription,
            'prescription_revision_reason_added',
            [
                'prescription_id' => $prescription->id,
                'revision_id' => $revision->id,
                'reason' => $reason,
            ],
            static::causerName($causer) . ' ha richiesto nuove modifiche: ' . $reason,
            $causer,
        );

        NotificationService::sendToUser(
            $prescription->user,
            new RequestPrescriptionRevisionForUser($prescription, $reason),
        );
    }

    /**
     * Close the currently open revision. Does not change prescription status.
     */
    public static function closeRevision(Prescription $prescription, ?User $causer = null): void
    {
        $revision = $prescription->activeRevision()->first();

        if ($revision === null) {
            throw ValidationException::withMessages([
                'prescription' => 'Non esiste una revisione aperta da chiudere',
            ]);
        }

        $revision->update([
            'closed_at' => now(),
            'closed_by' => $causer?->id,
        ]);

        static::logOnOperation(
            $prescription,
            'prescription_revision_closed',
            [
                'prescription_id' => $prescription->id,
                'revision_id' => $revision->id,
            ],
            static::causerName($causer) . ' ha chiuso la revisione',
            $causer,
        );

        NotificationService::sendToUser(
            $prescription->user,
            new ClosedPrescriptionRevisionForUser($prescription),
        );
    }

    /**
     * Customer resubmits the prescription while a revision is open.
     */
    protected static function resubmitRevision(Prescription $prescription, ?User $causer = null): void
    {
        $revision = $prescription->activeRevision()->first();

        if ($revision === null) {
            throw ValidationException::withMessages([
                'prescription' => 'La revisione è stata chiusa, non è più possibile inviare modifiche',
            ]);
        }

        $revision->update(['last_submitted_at' => now()]);

        $revisionNumber = $prescription->revisions()->count();

        static::logOnOperation(
            $prescription,
            'prescription_revision_resubmitted',
            [
                'prescription_id' => $prescription->id,
                'revision_id' => $revision->id,
                'revision_number' => $revisionNumber,
            ],
            static::causerName($causer) . ' ha inviato modifiche alla revisione #' . $revisionNumber,
            $causer,
        );

        NotificationService::sendToAdmins(new ResubmittedPrescriptionForAdmin($prescription, $revisionNumber));
    }

    /**
     * Log an activity event on the prescription's operation.
     *
     * @param  array<string, mixed>  $properties
     */
    protected static function logOnOperation(
        Prescription $prescription,
        string $event,
        array $properties,
        string $description,
        ?User $causer,
    ): void {
        $operation = $prescription->operation;

        if ($operation === null) {
            return;
        }

        $logger = activity()
            ->performedOn($operation)
            ->withProperties($properties)
            ->event($event);

        if ($causer !== null) {
            $logger->causedBy($causer);
        }

        $logger->log($description);
    }

    /**
     * Resolve causer full name for activity log description.
     */
    protected static function causerName(?User $causer): string
    {
        if ($causer === null) {
            return 'Sistema';
        }

        $full = trim(($causer->name ?? '') . ' ' . ($causer->surname ?? ''));

        return $full !== '' ? $full : 'Sistema';
    }

    /**
     * Validate prescription.
     */
    public static function validatePrescription(Prescription $prescription): bool
    {
        $valid = true;

        $valid = $valid && static::isNonBlankString($prescription->typology);
        $valid = $valid && static::isNonBlankString($prescription->ref);
        $valid = $valid && static::isNonBlankString($prescription->name);
        $valid = $valid && static::isNonBlankString($prescription->surname);
        $valid = $valid && $prescription->age !== null;
        $valid = $valid && static::isNonBlankString($prescription->gender);
        $valid = $valid && $prescription->building_id !== null;
        $valid = $valid && $prescription->user_id !== null;

        $typology = $prescription->typology;

        if ($typology === PrescriptionTypologyEnum::PROTRUSOR->value) {
            $prescription->loadMissing(['protrusorDetails']);
            $details = $prescription->protrusorDetails;

            $valid = $valid && $details !== null;
            $valid = $valid && static::isNonBlankString($details?->protrusor_typology);
            $valid = $valid && static::isNonBlankString($details?->jig);
            $valid = $valid && $details?->mandibular_advancement !== null;
            $valid = $valid && $details?->mandibular_advancement_2 !== null;

            if ($prescription->manual !== true) {
                $valid = $valid && static::hasMediaInCollections($details, [
                    'scansione_intraorale',
                    'rilevazione_dell_avanzamento_mandibolare_con_occlusione',
                ]);
            }
        } elseif ($typology === PrescriptionTypologyEnum::LYBRA_ALIGNER->value) {
            $prescription->loadMissing(['lybraAlignerDetails']);
            $details = $prescription->lybraAlignerDetails;

            $valid = $valid && $details !== null;
            $valid = $valid && static::isNonBlankString($details?->cut_line);

            if ($prescription->manual !== true) {
                $valid = $valid && static::hasMediaInCollections($details, [
                    'scansione_intraorale',
                    'foto_del_sorriso_e_morso_del_paziente',
                ]);
            }
        } elseif ($typology === PrescriptionTypologyEnum::GUIDED_SURGERY->value) {
            $prescription->loadMissing(['guidedSurgeryDetails']);
            $details = $prescription->guidedSurgeryDetails;

            $valid = $valid && $details !== null;
            $valid = $valid && static::isNonBlankString($details?->surgery_typology);
            $valid = $valid && static::hasAtLeastOneValue($details?->odontogram);
            $valid = $valid && static::isNonBlankString($details?->desired_implant_line);

            if ($prescription->manual !== true) {
                $valid = $valid && static::hasMediaInCollections($details, [
                    'scansione_intraorale',
                    'cbct_allineabile_con_la_scansione_rilevata',
                ]);
            }
        } elseif ($typology === PrescriptionTypologyEnum::THREE_D_MESH->value) {
            $prescription->loadMissing(['threeDMeshDetails']);
            $details = $prescription->threeDMeshDetails;

            $valid = $valid && $details !== null;
            $valid = $valid && static::isNonBlankString($details?->dimension);
            $valid = $valid && static::hasAtLeastOneValue($details?->odontogram);
            $valid = $valid && static::isNonBlankString($details?->{'3d_model'});

            if ($prescription->manual !== true) {
                $valid = $valid && static::hasMediaInCollections($details, [
                    'scansione_intraorale',
                    'cbct_allineabile_con_la_scansione_rilevata',
                ]);
            }
        } elseif ($typology === PrescriptionTypologyEnum::PROSTHESIS->value) {
            $prescription->loadMissing(['prosthesisDetails']);
            $details = $prescription->prosthesisDetails;

            $valid = $valid && $details !== null;
            $valid = $valid && static::isNonBlankString($details?->typology);

            if ($details?->typology === 'Corone e ponti') {
                $valid = $valid && static::isNonBlankString($details?->crowns_and_bridges_details);
            }
            if ($details?->typology === 'Full-arch') {
                $valid = $valid && static::isNonBlankString($details?->full_bridge_details);
            }

            $valid = $valid && static::hasAtLeastOneValue($details?->odontogram);
            $valid = $valid && static::isNonBlankString($details?->{'3d_normal_model'});
            $valid = $valid && static::isNonBlankString($details?->{'3d_excellent_model'});

            if ($prescription->manual !== true) {
                $valid = $valid && static::hasMediaInCollections($details, [
                    'scansione_intraorale',
                    'articolazione',
                ]);
            }
        } elseif ($typology === PrescriptionTypologyEnum::SEMI_FINISHED_PROSTHESES->value) {
            $prescription->loadMissing(['semiFinishedProsthesisDetails']);
            $details = $prescription->semiFinishedProsthesisDetails;

            $valid = $valid && $details !== null;
            $valid = $valid && static::isNonBlankString($details?->typology);

            if ($details?->typology === 'Corone e ponti') {
                $valid = $valid && static::isNonBlankString($details?->crowns_and_bridges_details);
            }
            if ($details?->typology === 'Full-arch') {
                $valid = $valid && static::isNonBlankString($details?->full_bridge_details);
            }

            $valid = $valid && static::hasAtLeastOneValue($details?->odontogram);

            if ($prescription->manual !== true) {
                $valid = $valid && static::hasMediaInCollections($details, [
                    'progetto_in_stl_da_fresare_oppure_scansione_digitale_completa',
                ]);
            }
        }

        return $valid;
    }

    /**
     * Check if value is a non-blank string.
     */
    private static function isNonBlankString(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        return trim($value) !== '';
    }

    /**
     * Check if value has at least one value.
     */
    private static function hasAtLeastOneValue(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $filtered = array_values(array_filter($value, static function (mixed $item): bool {
            if ($item === null) {
                return false;
            }

            if (is_string($item)) {
                return trim($item) !== '';
            }

            return true;
        }));

        return count($filtered) > 0;
    }

    /**
     * Check if value has media in collections.
     */
    private static function hasMediaInCollections(object|null $details, array $collections): bool
    {
        if ($details === null) {
            return false;
        }

        if (! method_exists($details, 'hasMedia')) {
            return false;
        }

        foreach ($collections as $collection) {
            if (! $details->hasMedia($collection)) {
                return false;
            }
        }

        return true;
    }
}
