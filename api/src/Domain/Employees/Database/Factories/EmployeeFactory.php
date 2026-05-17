<?php

namespace Domain\Employees\Database\Factories;

use Domain\Employees\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    /** {@inheritdoc} */
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'employee_role_id' => 1,
            'employee_location_id' => 1,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->e164PhoneNumber(),
            'employee_number' => $this->faker->unique()->numerify('EMP-#####'),
            'employment_type' => $this->faker->randomElement(['internal', 'external']),
            'start_date' => $this->faker->optional()->date(),
            'is_active' => $this->faker->boolean(90),
        ];
    }
}
