<?php

namespace App\Http\Requests\Operation;

use App\Enums\PrescriptionTypologyEnum;
use App\Enums\PrescriptionGenderEnum;
use App\Http\Requests\Prescription\Details\GuidedSurgeryDetailsValidator;
use App\Http\Requests\Prescription\Details\LybraAlignerDetailsValidator;
use App\Http\Requests\Prescription\Details\ProsthesisDetailsValidator;
use App\Http\Requests\Prescription\Details\ProtrusorDetailsValidator;
use App\Http\Requests\Prescription\Details\SemiFinishedProsthesesDetailsValidator;
use App\Http\Requests\Prescription\Details\ThreeDMeshDetailsValidator;
use App\Models\Operation;
use App\Models\Prescription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationPrescriptionRequest extends FormRequest
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
        $rules = [
            'user_id' => 'nullable|integer|exists:users,id',
            'typology' => ['nullable', 'string', Rule::in(PrescriptionTypologyEnum::toArray())],
            'ref' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'surname' => 'nullable|string|max:255',
            'age' => 'nullable|integer|min:0',
            'gender' => ['nullable', 'string', Rule::in(PrescriptionGenderEnum::toArray())],
        ];

        /** @var Prescription $prescription */
        $prescription = $this->route('prescription');
        $effectiveTypology = $this->input('typology') ?? $prescription->typology;

        if ($effectiveTypology === PrescriptionTypologyEnum::PROTRUSOR->value) {
            $rules = array_merge($rules, ProtrusorDetailsValidator::rules());
        }

        if ($effectiveTypology === PrescriptionTypologyEnum::LYBRA_ALIGNER->value) {
            $rules = array_merge($rules, LybraAlignerDetailsValidator::rules());
        }

        if ($effectiveTypology === PrescriptionTypologyEnum::GUIDED_SURGERY->value) {
            $rules = array_merge($rules, GuidedSurgeryDetailsValidator::rules());
        }

        if ($effectiveTypology === PrescriptionTypologyEnum::THREE_D_MESH->value) {
            $rules = array_merge($rules, ThreeDMeshDetailsValidator::rules());
        }

        if ($effectiveTypology === PrescriptionTypologyEnum::PROSTHESIS->value) {
            $rules = array_merge($rules, ProsthesisDetailsValidator::rules());
        }

        if ($effectiveTypology === PrescriptionTypologyEnum::SEMI_FINISHED_PROSTHESES->value) {
            $rules = array_merge($rules, SemiFinishedProsthesesDetailsValidator::rules());
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Operation $operation */
            $operation = $this->route('operation');
            /** @var Prescription $prescription */
            $prescription = $this->route('prescription');

            if ($prescription->operation_id !== $operation->id) {
                $validator->errors()->add('prescription', 'The selected prescription is not attached to this operation.');
            }
        });
    }
}
