<?php

namespace App\Http\Requests\SupplierChat;

use App\Models\Operation;
use App\Policies\OperationSupplierChatPolicy;
use Illuminate\Foundation\Http\FormRequest;

class ListOperationSupplierChatMessagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Operation $operation */
        $operation = $this->route('operation');

        return app(OperationSupplierChatPolicy::class)->viewMessages($this->user(), $operation);
    }

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ];
    }
}
