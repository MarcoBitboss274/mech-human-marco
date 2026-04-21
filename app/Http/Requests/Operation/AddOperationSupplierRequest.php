<?php

namespace App\Http\Requests\Operation;

use App\Enums\SupplierStatusEnum;
use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddOperationSupplierRequest extends FormRequest
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
        /** @var Operation $operation */
        $operation = $this->route('operation');

        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where(fn ($query) => $query->where('status', SupplierStatusEnum::ACTIVE->value)),
                function (string $attribute, mixed $value, \Closure $fail) use ($operation): void {
                    $isAlreadyAttached = $operation->suppliers()
                        ->where('suppliers.id', $value)
                        ->exists();

                    if ($isAlreadyAttached) {
                        $fail('The selected supplier is already attached to this operation.');
                    }
                },
            ],
        ];
    }
}
