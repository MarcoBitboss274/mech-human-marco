<?php

namespace App\Http\Requests\Prescription\Details;

use App\Enums\PrescriptionTypologyEnum;

class PrescriptionAttachmentsValidator
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function rules(string|null $typology, bool $requireFiles = false): array
    {
        if (! $typology) {
            return [];
        }

        if ($typology === PrescriptionTypologyEnum::PROTRUSOR->value) {
            return static::protrusor($requireFiles);
        }

        if ($typology === PrescriptionTypologyEnum::LYBRA_ALIGNER->value) {
            return static::lybraAligner($requireFiles);
        }

        if ($typology === PrescriptionTypologyEnum::GUIDED_SURGERY->value) {
            return static::guidedSurgery($requireFiles);
        }

        if ($typology === PrescriptionTypologyEnum::THREE_D_MESH->value) {
            return static::threeDMesh($requireFiles);
        }

        if ($typology === PrescriptionTypologyEnum::PROSTHESIS->value) {
            return static::prosthesis($requireFiles);
        }

        if ($typology === PrescriptionTypologyEnum::SEMI_FINISHED_PROSTHESES->value) {
            return static::semiFinishedProstheses($requireFiles);
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    private static function fileRules(bool $required): array
    {
        return [
            $required ? 'nullable' : 'nullable',
            'file',
            'max:10240',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function protrusor(bool $requireFiles): array
    {
        return [
            'protrusor_attachments' => ['nullable', 'array'],
            'protrusor_attachments.scansione_intraorale' => static::fileRules($requireFiles),
            'protrusor_attachments.rilevazione_dell_avanzamento_mandibolare_con_occlusione' => static::fileRules($requireFiles),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function lybraAligner(bool $requireFiles): array
    {
        return [
            'lybra_aligner_attachments' => ['nullable', 'array'],
            'lybra_aligner_attachments.scansione_intraorale' => static::fileRules($requireFiles),
            'lybra_aligner_attachments.foto_del_sorriso_e_morso_del_paziente' => static::fileRules($requireFiles),
            'lybra_aligner_attachments.ortopantomografia' => static::fileRules($requireFiles),
            'lybra_aligner_attachments.rx_anteroposteriore_delle_ossa_mascellari' => static::fileRules($requireFiles),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function guidedSurgery(bool $requireFiles): array
    {
        return [
            'guided_surgery_attachments' => ['nullable', 'array'],
            'guided_surgery_attachments.scansione_intraorale' => static::fileRules($requireFiles),
            'guided_surgery_attachments.cbct_allineabile_con_la_scansione_rilevata' => static::fileRules($requireFiles),
            'guided_surgery_attachments.ceratura_diagnostica' => static::fileRules($requireFiles),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function threeDMesh(bool $requireFiles): array
    {
        return [
            'three_d_mesh_attachments' => ['nullable', 'array'],
            'three_d_mesh_attachments.scansione_intraorale' => static::fileRules($requireFiles),
            'three_d_mesh_attachments.cbct_allineabile_con_la_scansione_rilevata' => static::fileRules($requireFiles),
            'three_d_mesh_attachments.ceratura_diagnostica' => static::fileRules($requireFiles),
            'three_d_mesh_attachments.scansione_facciale_o_foto_del_sorriso' => static::fileRules($requireFiles),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function prosthesis(bool $requireFiles): array
    {
        return [
            'prosthesis_attachments' => ['nullable', 'array'],
            'prosthesis_attachments.scansione_intraorale' => static::fileRules($requireFiles),
            'prosthesis_attachments.articolazione' => static::fileRules($requireFiles),
            'prosthesis_attachments.foto_con_campione_colore' => static::fileRules($requireFiles),
            'prosthesis_attachments.foto_del_sorriso' => static::fileRules($requireFiles),
            'prosthesis_attachments.scansione_e_foto_del_provvisorio' => static::fileRules($requireFiles),
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function semiFinishedProstheses(bool $requireFiles): array
    {
        return [
            'semi_finished_prostheses_attachments' => ['nullable', 'array'],
            'semi_finished_prostheses_attachments.progetto_in_stl_da_fresare_oppure_scansione_digitale_completa' => static::fileRules($requireFiles),
            'semi_finished_prostheses_attachments.scansione_del_provvisorio' => static::fileRules($requireFiles),
        ];
    }
}
