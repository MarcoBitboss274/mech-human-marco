<?php

namespace App\Http\Requests\Workspace;

use Illuminate\Foundation\Http\FormRequest;

class StoreBuildingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isCustomer();
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
