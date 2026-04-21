<?php

namespace App\Services;

use App\Enums\OperationStatusEnum;
use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Models\Prescription;
use App\Notifications\Admin\SendPrescriptionForAdmin;
use App\Notifications\Agent\SendPrescriptionForAgent;
use App\Notifications\User\SendPrescriptionForUser;
use App\Services\NotificationService;
use App\Services\OperationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
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
        ]);

        return [
            'prescription' => $prescription->toArray(),
        ];
    }

    /**
     * Mark prescription as sent.
     */
    public static function sendPrescription(Prescription $prescription): void
    {
        if (! static::validatePrescription($prescription)) {
            throw ValidationException::withMessages([
                'prescription' => 'Prescrizione non valida, verificare tutti i campi',
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
     * Mark prescription as confirmed.
     */
    public static function confirmPrescription(Prescription $prescription): void
    {
        $prescription->update([
            'status' => PrescriptionStatusEnum::CONFIRMED->value,
        ]);

        OperationService::updateStatus($prescription->operation, OperationStatusEnum::IN_PROGRESS->value);
    }

    /**
     * Reset prescription status to draft.
     */
    public static function resetPrescription(Prescription $prescription): void
    {
        $prescription->update([
            'status' => PrescriptionStatusEnum::DRAFT->value,
            'send_at' => null,
            'expire_at' => null,
        ]);

        OperationService::updateStatus($prescription->operation, OperationStatusEnum::DRAFT->value);
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
