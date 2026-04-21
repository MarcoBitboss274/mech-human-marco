<?php

namespace App\Http\Requests\Production;

use App\Enums\ProductionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operation_id' => 'nullable|integer|exists:operations,id',
            'status' => ['nullable', 'string', Rule::in(ProductionStatusEnum::toArray())],
        ];
    }
}

