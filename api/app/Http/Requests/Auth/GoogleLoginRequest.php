<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class GoogleLoginRequest extends FormRequest
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
            // The Google ID-token credential from @react-oauth/google.
            // The token is verified server-side; nothing here is trusted.
            'credential' => ['required', 'string', 'max:12000'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ];
    }
}
