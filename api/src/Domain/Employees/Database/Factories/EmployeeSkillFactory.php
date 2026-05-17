<?php

namespace Domain\Employees\Database\Factories;

use Domain\Employees\Models\Employee;
use Domain\Employees\Models\EmployeeSkill;
use Domain\Employees\Models\Skill;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeSkill> */
class EmployeeSkillFactory extends Factory
{
    /** {@inheritdoc} */
    protected $model = EmployeeSkill::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'skill_id' => Skill::factory(),
            'level' => $this->faker->numberBetween(1, 5),
            'notes' => $this->faker->optional()->sentence(),
            'assessed_at' => $this->faker->optional()->dateTime(),
        ];
    }
}
