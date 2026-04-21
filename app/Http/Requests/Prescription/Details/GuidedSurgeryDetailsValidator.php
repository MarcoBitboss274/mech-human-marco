<?php

namespace App\Http\Requests\Prescription\Details;

class GuidedSurgeryDetailsValidator
{
    /**
     * Get validation rules for guided surgery details.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(bool $requireTypology = false): array
    {
        return [
            'guided_surgery_details' => ['nullable', 'array'],
            'guided_surgery_details.surgery_typology' => [
                $requireTypology ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
            'guided_surgery_details.odontogram' => ['nullable', 'array'],
            'guided_surgery_details.odontogram.*' => ['integer'],
            'guided_surgery_details.desired_implant_line' => ['nullable', 'string', 'max:255'],
            'guided_surgery_details.additional_info' => ['nullable', 'string'],
            'guided_surgery_details.note' => ['nullable', 'string'],
        ];
    }
}
