<?php

namespace App\Enums;

enum PrescriptionGenderEnum: string
{
    use BaseEnum;

    case MALE = 'male';
    case FEMALE = 'female';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Maschio',
            self::FEMALE => 'Femmina',
            self::OTHER => 'Altro',
        };
    }
}
