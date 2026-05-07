<?php

namespace App\Http\Requests\SupplierChat;

use App\Models\Operation;
use App\Policies\OperationSupplierChatPolicy;
use Illuminate\Foundation\Http\FormRequest;

class MarkOperationSupplierChatAsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Operation $operation */
        $operation = $this->route('operation');

        return app(OperationSupplierChatPolicy::class)->markAsRead($this->user(), $operation);
    }

    public function rules(): array
    {
        return [];
    }
}
