<?php

namespace App\Http\Requests\Prescription\Details;

class SemiFinishedProsthesesDetailsValidator
{
    /**
     * Get validation rules for semi-finished prostheses details.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(bool $requireTypology = false): array
    {
        return [
            'semi_finished_prostheses_details' => ['nullable', 'array'],
            'semi_finished_prostheses_details.typology' => [
                $requireTypology ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
            'semi_finished_prostheses_details.crowns_and_bridges_details' => ['nullable', 'string', 'max:255'],
            'semi_finished_prostheses_details.full_bridge_details' => ['nullable', 'string', 'max:255'],
            'semi_finished_prostheses_details.odontogram' => ['nullable', 'array'],
            'semi_finished_prostheses_details.odontogram.*' => ['integer'],
            'semi_finished_prostheses_details.note' => ['nullable', 'string'],
        ];
    }
}
