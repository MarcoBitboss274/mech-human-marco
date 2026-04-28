<?php

namespace App\Http\Requests\Operation;

use App\Enums\PrescriptionGenderEnum;
use App\Enums\PrescriptionStatusEnum;
use App\Enums\PrescriptionTypologyEnum;
use App\Enums\WorkspaceAbilityEnum;
use App\Http\Requests\Prescription\Details\GuidedSurgeryDetailsValidator;
use App\Http\Requests\Prescription\Details\LybraAlignerDetailsValidator;
use App\Http\Requests\Prescription\Details\PrescriptionAttachmentsValidator;
use App\Http\Requests\Prescription\Details\ProsthesisDetailsValidator;
use App\Http\Requests\Prescription\Details\ProtrusorDetailsValidator;
use App\Http\Requests\Prescription\Details\SemiFinishedProsthesesDetailsValidator;
use App\Http\Requests\Prescription\Details\ThreeDMeshDetailsValidator;
use App\Models\Building;
use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationWithPrescriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->routeIs('workspace.*')) {
            return true;
        }

        $user = $this->user();
        if (! $user || ! $user->isCustomer()) {
            return false;
        }

        /** @var Building|null $building */
        $building = $this->route('building');
        if (! $building instanceof Building) {
            return false;
        }

        return $user->can('workspaceAbility', [$building, WorkspaceAbilityEnum::OPERATIONS_EDIT->value]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isDraft = $this->boolean('draft');
        $isManual = $this->boolean('manual');
        $requireFiles = ! $isDraft && ! $isManual;

        $rules = [
            'draft' => 'sometimes|boolean',
            'submit_revision' => 'sometimes|boolean',
            'building_id' => 'required|integer|exists:buildings,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'typology' => ['required', 'string', Rule::in(PrescriptionTypologyEnum::toArray())],
            'ref' => 'nullable|string|max:255',
            'manual' => 'nullable|boolean',
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'age' => 'nullable|integer|min:0',
            'gender' => ['nullable', 'string', Rule::in(PrescriptionGenderEnum::toArray())],
            'company_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'cap' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];

        if ($this->input('typology') === PrescriptionTypologyEnum::PROTRUSOR->value) {
            $rules = array_merge($rules, ProtrusorDetailsValidator::rules());
        }

        if ($this->input('typology') === PrescriptionTypologyEnum::LYBRA_ALIGNER->value) {
            $rules = array_merge($rules, LybraAlignerDetailsValidator::rules());
        }

        if ($this->input('typology') === PrescriptionTypologyEnum::GUIDED_SURGERY->value) {
            $rules = array_merge($rules, GuidedSurgeryDetailsValidator::rules());
        }

        if ($this->input('typology') === PrescriptionTypologyEnum::THREE_D_MESH->value) {
            $rules = array_merge($rules, ThreeDMeshDetailsValidator::rules());
        }

        if ($this->input('typology') === PrescriptionTypologyEnum::PROSTHESIS->value) {
            $rules = array_merge($rules, ProsthesisDetailsValidator::rules());
        }

        if ($this->input('typology') === PrescriptionTypologyEnum::SEMI_FINISHED_PROSTHESES->value) {
            $rules = array_merge($rules, SemiFinishedProsthesesDetailsValidator::rules());
        }

        $rules = array_merge($rules, PrescriptionAttachmentsValidator::rules(
            $this->input('typology'),
            $requireFiles,
        ));

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
            $latestPrescription = $operation->latestPrescription()->first();

            if (! $latestPrescription) {
                $validator->errors()->add('operation', 'The selected operation has no linked prescription.');
                return;
            }

            $latestPrescription->loadMissing('activeRevision');
            $hasActiveRevision = $latestPrescription->activeRevision !== null;
            $isDraftPrescription = $latestPrescription->status === PrescriptionStatusEnum::DRAFT->value;

            if (! $isDraftPrescription && ! $hasActiveRevision) {
                $validator->errors()->add('operation', 'Only draft prescriptions or prescriptions with an open revision can be edited from the wizard.');
            }
        });
    }
}
