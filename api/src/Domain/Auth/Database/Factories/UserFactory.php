<?php

namespace Domain\Auth\Database\Factories;

use Domain\Auth\Enums\UserStatus;
use Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /** {@inheritdoc} */
    protected $model = User::class;

    /** {@inheritdoc} */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'password_set_at' => now(),
            'two_factor_confirmed_at' => now(),
            'onboarding_completed_at' => now(),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function withoutTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => null,
            'two_factor_pending_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null,
            'onboarding_completed_at' => null,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
