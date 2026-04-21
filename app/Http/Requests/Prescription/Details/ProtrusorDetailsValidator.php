<?php

namespace App\Http\Requests\Prescription\Details;

class ProtrusorDetailsValidator
{
    /**
     * Get validation rules for protrusor details.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(bool $requireTypology = false): array
    {
        return [
            'protrusor_details' => ['nullable', 'array'],
            'protrusor_details.protrusor_typology' => [
                $requireTypology ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
            'protrusor_details.odontogram' => ['nullable', 'array'],
            'protrusor_details.odontogram.*' => ['integer'],
            'protrusor_details.jig' => ['nullable', 'string', 'max:255'],
            'protrusor_details.remaining_upper_teeth' => ['nullable', 'string', 'max:255'],
            'protrusor_details.remaining_lower_teeth' => ['nullable', 'string', 'max:255'],
            'protrusor_details.transpalatal_arch' => ['nullable', 'string', 'max:255'],
            'protrusor_details.mandibular_advancement' => ['nullable', 'numeric'],
            'protrusor_details.mandibular_advancement_2' => ['nullable', 'numeric'],
            'protrusor_details.note' => ['nullable', 'string'],
        ];
    }
}