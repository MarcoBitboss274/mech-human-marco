<?php

namespace App\Http\Requests\Workspace;

use App\Enums\WorkspaceAbilityEnum;
use App\Models\Building;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBuildingMemberRoleRequest extends FormRequest
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

        return $user->can('workspaceAbility', [$building, WorkspaceAbilityEnum::BUILDING_MEMBERS_EDIT->value]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => 'required|in:admin,member',
        ];
    }
}
