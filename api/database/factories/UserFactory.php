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
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'is_superuser' => false,
            'role' => User::ROLE_OWNER,
        ];
    }

    public function member(): static
    {
        return $this->state(fn () => ['role' => User::ROLE_MEMBER]);
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
}
