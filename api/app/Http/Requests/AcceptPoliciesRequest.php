<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcceptPoliciesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $terms = (string) config('legal.terms_version');
        $privacy = (string) config('legal.privacy_version');

        return [
            'terms_version' => ['required', 'string', "in:{$terms}"],
            'privacy_version' => ['required', 'string', "in:{$privacy}"],
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
            'terms_version.in' => 'Stale terms_version. Current is '.config('legal.terms_version').'.',
            'privacy_version.in' => 'Stale privacy_version. Current is '.config('legal.privacy_version').'.',
            'terms_accepted.accepted' => 'You must accept the Terms of Service to continue.',
            'privacy_accepted.accepted' => 'You must accept the Privacy Policy to continue.',
        ];
    }
}
