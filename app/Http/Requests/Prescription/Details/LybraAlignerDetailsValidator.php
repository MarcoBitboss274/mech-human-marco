<?php

namespace App\Http\Requests\Prescription\Details;

class LybraAlignerDetailsValidator
{
    /**
     * Get validation rules for lybra aligner details.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(bool $requireCutLine = false): array
    {
        return [
            'lybra_aligner_details' => ['nullable', 'array'],
            'lybra_aligner_details.cut_line' => [
                $requireCutLine ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
            'lybra_aligner_details.note' => ['nullable', 'string'],
        ];
    }
}
