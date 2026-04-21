<?php

namespace App\Http\Requests\Prescription\Details;

class ThreeDMeshDetailsValidator
{
    /**
     * Get validation rules for 3D mesh details.
     *
     * @return array<string, array<int, string>>
     */
    public static function rules(bool $requireDimension = false): array
    {
        return [
            'three_d_mesh_details' => ['nullable', 'array'],
            'three_d_mesh_details.dimension' => [
                $requireDimension ? 'required' : 'nullable',
                'string',
                'max:255',
            ],
            'three_d_mesh_details.odontogram' => ['nullable', 'array'],
            'three_d_mesh_details.odontogram.*' => ['integer'],
            'three_d_mesh_details.outer_finish' => ['nullable', 'string', 'max:255'],
            'three_d_mesh_details.inner_finish' => ['nullable', 'string', 'max:255'],
            'three_d_mesh_details.pattern' => ['nullable', 'string', 'max:255'],
            'three_d_mesh_details.stress_breakers' => ['nullable', 'string', 'max:255'],
            'three_d_mesh_details.3d_model' => ['nullable', 'string', 'max:255'],
            'three_d_mesh_details.screw_diameter' => ['nullable', 'numeric'],
            'three_d_mesh_details.shared_project_note' => ['nullable', 'string'],
            'three_d_mesh_details.note' => ['nullable', 'string'],
        ];
    }
}
