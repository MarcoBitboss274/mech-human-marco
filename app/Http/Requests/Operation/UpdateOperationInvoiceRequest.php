<?php

namespace App\Http\Requests\Operation;

use App\Models\Invoice;
use App\Models\Operation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationInvoiceRequest extends FormRequest
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
            'description' => ['required', 'string'],
            'file' => ['nullable', 'file', 'mimetypes:application/pdf', 'max:10240'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var Operation $operation */
            $operation = $this->route('operation');
            /** @var Invoice $invoice */
            $invoice = $this->route('invoice');

            if ($invoice->operation_id !== $operation->id) {
                $validator->errors()->add('invoice', 'The selected invoice is not attached to this operation.');
            }
        });
    }
}