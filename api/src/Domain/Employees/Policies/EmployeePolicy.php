<?php

namespace Domain\Employees\Policies;

use Domain\Employees\Models\Employee;
use Domain\Support\Policies\BasePolicy;

class EmployeePolicy extends BasePolicy
{
    protected function model(): string
    {
        return Employee::class;
    }
}
