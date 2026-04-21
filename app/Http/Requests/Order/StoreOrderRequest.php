<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
        $orderId = $this->route('order')?->id;

        return [
            'operation_id' => 'nullable|integer|exists:operations,id',
            'status' => ['nullable', 'string', Rule::in(OrderStatusEnum::toArray())],
            'amount' => [$this->isMethod('post') ? 'required' : 'nullable', 'numeric'],
            'description' => [$this->isMethod('post') ? 'required' : 'nullable', 'string'],
            'code' => ['nullable', 'string', 'size:8', Rule::unique('orders', 'code')->ignore($orderId)],
        ];
    }
}
