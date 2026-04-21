<?php

namespace App\Http\Requests\Prescription;

use App\Enums\PrescriptionTypologyEnum;
use App\Enums\PrescriptionGenderEnum;
use App\Enums\PrescriptionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrescriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'operation_id' => 'nullable|integer|exists:operations,id',
            'building_id' => 'nullable|integer|exists:buildings,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'status' => ['nullable', 'string', Rule::in(PrescriptionStatusEnum::toArray())],
            'typology' => ['nullable', 'string', Rule::in(PrescriptionTypologyEnum::toArray())],
            'ref' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'age' => 'nullable|integer|min:0',
            'gender' => ['nullable', 'string', Rule::in(PrescriptionGenderEnum::toArray())],
        ];
    }
}
