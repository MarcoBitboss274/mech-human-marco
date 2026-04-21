<?php

namespace App\Http\Requests\Building;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuildingRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'vat' => 'required|string|max:255',
            'agent_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', RoleEnum::AGENT->value)
                    ->where('active', true),
            ],
            'is_studio' => 'nullable|boolean',
            'customer_code' => 'nullable|string|max:255',
            'is_laboratory' => 'nullable|boolean',
            'headquarter_address' => 'nullable|string|max:255',
            'legal_address' => 'nullable|string|max:255',
            'approved' => 'nullable|boolean',
            'fiscal_code' => 'nullable|string|max:255',
            'sdi_code' => 'nullable|string|max:255',
        ];
    }
}