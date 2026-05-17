<?php

namespace Domain\Employees\Policies;

use Domain\Employees\Models\EmployeeLocation;
use Domain\Support\Policies\BasePolicy;

class EmployeeLocationPolicy extends BasePolicy
{
    protected function model(): string
    {
        return EmployeeLocation::class;
    }
}
