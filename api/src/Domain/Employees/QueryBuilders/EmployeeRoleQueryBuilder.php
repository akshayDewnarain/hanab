<?php

namespace Domain\Employees\QueryBuilders;

use Domain\Employees\Helpers\EmployeeRoleHelper;
use Domain\Support\QueryBuilders\BaseBuilder;

class EmployeeRoleQueryBuilder extends BaseBuilder
{
    public function helper(): string
    {
        return EmployeeRoleHelper::class;
    }
}
