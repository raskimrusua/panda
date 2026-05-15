<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Factory-created users start already-consented to the current
        // legal versions so tests aren't silently 409'd by ConsentGate.
        // Use ->stale() to simulate a user with out-of-date versions.
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_superuser' => false,
            'terms_accepted_at' => now(),
            'terms_version' => (string) config('legal.terms_version'),
            'privacy_accepted_at' => now(),
            'privacy_version' => (string) config('legal.privacy_version'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function superuser(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_superuser' => true,
        ]);
    }

    public function withoutTenant(): static
    {
        return $this->state(fn () => ['tenant_id' => null]);
    }

    public function stale(): static
    {
        return $this->state(fn () => [
            'terms_accepted_at' => null,
            'terms_version' => '',
            'privacy_accepted_at' => null,
            'privacy_version' => '',
        ]);
    }
}
