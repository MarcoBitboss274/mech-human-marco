<?php

namespace App\Http\Requests\Chat;

use App\Models\Operation;
use App\Policies\OperationChatPolicy;
use Illuminate\Foundation\Http\FormRequest;

class MarkOperationChatAsReadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Operation $operation */
        $operation = $this->route('operation');

        return
            $this->user()->canAccessOperation($operation) ||
            app(OperationChatPolicy::class)->markAsRead($this->user(), $operation);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}