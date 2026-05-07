<?php

namespace App\Http\Requests\SupplierChat;

use App\Models\Operation;
use App\Policies\OperationSupplierChatPolicy;
use Illuminate\Foundation\Http\FormRequest;

class SendOperationSupplierChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Operation $operation */
        $operation = $this->route('operation');

        return app(OperationSupplierChatPolicy::class)->sendMessage($this->user(), $operation);
    }

    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
