<?php

namespace Domain\Employees\Policies;

use Domain\Employees\Models\EmployeeSkill;
use Domain\Support\Policies\BasePolicy;

class EmployeeSkillPolicy extends BasePolicy
{
    protected function model(): string
    {
        return EmployeeSkill::class;
    }
}
