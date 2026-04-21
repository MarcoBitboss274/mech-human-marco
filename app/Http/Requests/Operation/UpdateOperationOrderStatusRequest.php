<?php

namespace App\Http\Requests\Operation;

use App\Enums\OrderStatusEnum;
use App\Models\Operation;
use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationOrderStatusRequest extends FormRequest
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
        /** @var Order $order */
        $order = $this->route('order');

        return [
            'status' => [
                'required',
                'string',
                Rule::in(OrderStatusEnum::toArray()),
                function (string $attribute, mixed $value, \Closure $fail) use ($operation, $order): void {
                    if ($order->operation_id !== $operation->id) {
                        $fail('The selected order is not attached to this operation.');
                    }
                },
            ],
        ];
    }
}
