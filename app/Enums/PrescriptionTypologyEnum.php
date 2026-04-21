<?php

namespace App\Enums;

enum PrescriptionTypologyEnum: string
{
    use BaseEnum;

    case PROTRUSOR = 'protrusor';
    case LYBRA_ALIGNER = 'lybra_aligner';
    case GUIDED_SURGERY = 'guided_surgery';
    case THREE_D_MESH = '3d_mesh';
    case PROSTHESIS = 'prosthesis';
    case SEMI_FINISHED_PROSTHESES = 'semi_finished_prostheses';

    public function label(): string
    {
        return match ($this) {
            self::PROTRUSOR => 'Protrusor',
            self::LYBRA_ALIGNER => 'Lybra Aligner',
            self::GUIDED_SURGERY => 'Chirurgia guidata',
            self::THREE_D_MESH => '3D Mesh',
            self::PROSTHESIS => 'Protesi',
            self::SEMI_FINISHED_PROSTHESES => 'Semilavorato di protesi',
        };
    }
}
