<?php

namespace App\Http\Requests\Operation;

use App\Enums\QuoteStatusEnum;
use App\Models\Operation;
use App\Models\Quote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationQuoteStatusRequest extends FormRequest
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
        /** @var Quote $quote */
        $quote = $this->route('quote');

        return [
            'status' => [
                'required',
                'string',
                Rule::in(QuoteStatusEnum::toArray()),
                function (string $attribute, mixed $value, \Closure $fail) use ($operation, $quote): void {
                    if ($quote->operation_id !== $operation->id) {
                        $fail('The selected quote is not attached to this operation.');
                    }
                },
            ],
        ];
    }
}
