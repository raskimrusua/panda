<?php

namespace App\Http\Requests\Team;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isOwner() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                // Cannot invite someone who already has a User in this tenant.
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $this->user()->tenant_id)),
            ],
            'name' => ['nullable', 'string', 'min:2', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A user with this email is already on the team.',
        ];
    }

    public function authorizationFailureMessage(): string
    {
        return 'Only the farm owner can invite team members.';
    }

    public function failedAuthorization(): never
    {
        abort(403, $this->authorizationFailureMessage());
    }

    public function ownerCheck(): bool
    {
        return $this->user() instanceof User && $this->user()->isOwner();
    }
}
