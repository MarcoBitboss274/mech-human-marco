<?php

namespace App\Http\Requests\Operation;

use App\Enums\OperationSupplierStatusEnum;
use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationSupplierStatusRequest extends FormRequest
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
                Rule::exists('suppliers', 'id'),
                function (string $attribute, mixed $value, \Closure $fail) use ($operation): void {
                    $isAttached = $operation->suppliers()
                        ->where('suppliers.id', $value)
                        ->exists();

                    if (! $isAttached) {
                        $fail('The selected supplier is not attached to this operation.');
                    }
                },
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(OperationSupplierStatusEnum::toArray()),
            ],
        ];
    }
}
