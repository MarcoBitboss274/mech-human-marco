<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class InviteSupplierMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('suppliers.members.manage') ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email|max:255',
            'role' => 'required|string|in:admin,member',
        ];
    }
}
