<?php

namespace Domain\Employees\Database\Factories;

use Domain\Employees\Models\EmployeeLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeLocation> */
class EmployeeLocationFactory extends Factory
{
    /** {@inheritdoc} */
    protected $model = EmployeeLocation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->city(),
            'cluster_name' => $this->faker->randomElement(['Cluster North', 'Cluster South', 'Cluster Central']),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
