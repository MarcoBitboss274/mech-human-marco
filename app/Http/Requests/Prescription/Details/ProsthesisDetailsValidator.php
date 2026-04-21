<?php

namespace App\Http\Requests\Prescription\Details;

class ProsthesisDetailsValidator
{
    /**
     * Get validation rules for prosthesis details.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(bool $requireTypology = false): array
    {
        return [
            'prosthesis_details' => ['nullable', 'array'],
            'prosthesis_details.typology' => [
                $requireTypology ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
            'prosthesis_details.crowns_and_bridges_details' => ['nullable', 'string', 'max:255'],
            'prosthesis_details.full_bridge_details' => ['nullable', 'string', 'max:255'],
            'prosthesis_details.odontogram' => ['nullable', 'array'],
            'prosthesis_details.odontogram.*' => ['integer'],
            'prosthesis_details.3d_normal_model' => ['nullable', 'string', 'max:255'],
            'prosthesis_details.3d_excellent_model' => ['nullable', 'string', 'max:255'],
            'prosthesis_details.note' => ['nullable', 'string'],
        ];
    }
}
