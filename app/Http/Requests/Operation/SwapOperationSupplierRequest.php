<?php

namespace App\Http\Requests\Operation;

use App\Enums\SupplierStatusEnum;
use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SwapOperationSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

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
                    $isCurrent = $operation->suppliers()
                        ->wherePivot('selected', true)
                        ->where('suppliers.id', $value)
                        ->exists();

                    if ($isCurrent) {
                        $fail('Il fornitore selezionato è già quello attuale.');
                    }
                },
            ],
        ];
    }
}
