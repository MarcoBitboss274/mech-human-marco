<?php

namespace App\Http\Requests\Operation;

use App\Models\Operation;
use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOperationQuoteRequest extends FormRequest
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
            'notes' => 'nullable|string',
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
            /** @var Quote $quote */
            $quote = $this->route('quote');

            if ($quote->operation_id !== $operation->id) {
                $validator->errors()->add('quote', 'The selected quote is not attached to this operation.');
            }
        });
    }
}
