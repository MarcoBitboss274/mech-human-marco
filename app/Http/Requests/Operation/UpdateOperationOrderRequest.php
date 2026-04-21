<?php

namespace App\Http\Requests\Operation;

use App\Enums\OrderStatusEnum;
use App\Models\Operation;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationOrderRequest extends FormRequest
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
            'amount' => 'nullable|numeric',
            'description' => 'nullable|string',
            'status' => [
                'nullable',
                'string',
                Rule::in(OrderStatusEnum::toArray()),
            ],
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
            /** @var Order $order */
            $order = $this->route('order');

            if ($order->operation_id !== $operation->id) {
                $validator->errors()->add('order', 'The selected order is not attached to this operation.');
            }
        });
    }
}
