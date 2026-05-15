<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'farm_name' => ['required', 'string', 'min:2', 'max:200'],
            'county' => ['required', 'string', 'max:64'],
            'sub_county' => ['nullable', 'string', 'max:64'],
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            // Kenya DPA 2019 §30 — consent must be informed + specific. PWA
            // enforces a required checkbox before submit; this server-side
            // rule is the second line of defence against a tampered client.
            'terms_accepted' => ['required', 'accepted'],
            'privacy_accepted' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'terms_accepted.accepted' => 'You must accept the Terms of Service to continue.',
            'privacy_accepted.accepted' => 'You must accept the Privacy Policy to continue.',
        ];
    }
}
