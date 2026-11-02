<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SubscriptionPayment> */
class SubscriptionPaymentFactory extends Factory
{
    protected $model = SubscriptionPayment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'subscription_id' => Subscription::factory(),
            'amount' => 200,
            'currency' => 'KES',
            'phone' => '254712345678',
            'payment_method' => SubscriptionPayment::PAYMENT_MPESA,
            'status' => SubscriptionPayment::STATUS_PENDING,
        ];
    }
}
