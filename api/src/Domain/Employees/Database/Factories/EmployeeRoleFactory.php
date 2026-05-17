<?php

namespace Domain\Employees\Database\Factories;

use Domain\Employees\Models\EmployeeRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeRole> */
class EmployeeRoleFactory extends Factory
{
    /** {@inheritdoc} */
    protected $model = EmployeeRole::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
