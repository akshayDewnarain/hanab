<?php

namespace Domain\Employees\Database\Factories;

use Domain\Employees\Models\Certificate;
use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeCertificate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeCertificate> */
class EmployeeCertificateFactory extends Factory
{
    /** {@inheritdoc} */
    protected $model = EmployeeCertificate::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'certificate_id' => Certificate::factory(),
            'certificate_number' => $this->faker->optional()->numerify('CERT-########'),
            'issued_at' => $this->faker->optional()->date(),
            'expires_at' => $this->faker->optional()->date(),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
