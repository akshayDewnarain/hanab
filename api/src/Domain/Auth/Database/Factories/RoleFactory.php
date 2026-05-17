<?php

namespace Domain\Auth\Database\Factories;

use Domain\Auth\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /** {@inheritdoc} */
    protected $model = Role::class;

    /** {@inheritdoc} */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->slug(2),
            'guard_name' => config('auth.defaults.guard'),
        ];
    }
}
