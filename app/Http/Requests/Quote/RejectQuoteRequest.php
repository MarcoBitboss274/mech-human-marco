<?php

namespace App\Http\Requests\Quote;

use App\Enums\WorkspaceAbilityEnum;
use App\Models\Building;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class RejectQuoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        if (! $this->routeIs('workspace.*')) {
            return true;
        }

        $user = $this->user();
        if (! $user || ! $user->isCustomer()) {
            return false;
        }

        /** @var Building|null $building */
        $building = $this->route('building');
        if (! $building instanceof Building) {
            return false;
        }

        return $user->can('workspaceAbility', [$building, WorkspaceAbilityEnum::QUOTES_REJECT->value]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! is_string($value) || trim($value) === '') {
                        $fail('The notes field is required.');
                    }
                },
            ],
        ];
    }
}
