<?php

namespace Domain\Employees\Policies;

use Domain\Employees\Models\EmployeeRole;
use Domain\Support\Policies\BasePolicy;

class EmployeeRolePolicy extends BasePolicy
{
    protected function model(): string
    {
        return EmployeeRole::class;
    }
}
