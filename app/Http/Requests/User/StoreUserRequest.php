<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Enums\RoleEnum;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
        $model = $this->route('user');

        $updating =  ! is_null($model);

        return [
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email'  . (($updating) ? (',' . $model->id) : ''),
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'role' => 'required|string|in:' . implode(',', RoleEnum::toArray()),
            'active' => 'required|boolean',
            'send_invite' => 'required|boolean',
            'odontoiatra' => 'required|boolean',
            'odontotecnico' => 'required|boolean',
            'roll_number' => 'required_if:odontoiatra,true|nullable|string|max:255',
            'roll_province' => 'required_if:odontoiatra,true|nullable|string|max:255',
            'building_relations' => 'nullable|array',
            'building_relations.*.building_id' => 'nullable|integer|exists:buildings,id|distinct',
            'building_relations.*.role' => 'required_with:building_relations.*.building_id|string|in:admin,member',
            'managed_building_ids' => 'nullable|array',
            'managed_building_ids.*' => 'nullable|integer|exists:buildings,id|distinct',
            'verify_email' => 'required|boolean',
        ];
    }
}