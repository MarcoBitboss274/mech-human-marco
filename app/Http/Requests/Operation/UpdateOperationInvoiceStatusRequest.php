<?php

namespace App\Http\Requests\Operation;

use App\Enums\InvoiceStatusEnum;
use App\Models\Invoice;
use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationInvoiceStatusRequest extends FormRequest
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
        /** @var Invoice $invoice */
        $invoice = $this->route('invoice');

        return [
            'status' => [
                'required',
                'string',
                Rule::in(InvoiceStatusEnum::toArray()),
                function (string $attribute, mixed $value, \Closure $fail) use ($operation, $invoice): void {
                    if ($invoice->operation_id !== $operation->id) {
                        $fail('The selected invoice is not attached to this operation.');
                    }
                },
            ],
        ];
    }
}
