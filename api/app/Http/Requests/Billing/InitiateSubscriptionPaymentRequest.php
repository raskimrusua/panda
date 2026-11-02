<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InitiateSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:40'],
            'plan_slug' => ['required', 'string', 'max:80', Rule::exists('subscription_plans', 'slug')->where('is_active', true)],
        ];
    }
}
