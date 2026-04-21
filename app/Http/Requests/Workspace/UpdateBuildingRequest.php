<?php

namespace App\Http\Requests\Workspace;

use App\Enums\WorkspaceAbilityEnum;
use App\Models\Building;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->isCustomer()) {
            return false;
        }

        /** @var Building|null $building */
        $building = $this->route('building');
        if (! $building instanceof Building) {
            return false;
        }

        return $user->can('workspaceAbility', [$building, WorkspaceAbilityEnum::BUILDING_UPDATE->value]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'vat' => 'required|string|max:255',
            'is_studio' => 'nullable|boolean',
            'customer_code' => 'nullable|string|max:255',
            'is_laboratory' => 'nullable|boolean',
            'headquarter_address' => 'required|string|max:255',
            'legal_address' => 'required|string|max:255',
            'fiscal_code' => 'nullable|string|max:255',
            'sdi_code' => 'nullable|string|max:255',
        ];
    }
}
