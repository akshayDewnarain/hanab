<?php

namespace Domain\Employees\QueryBuilders;

use Domain\Employees\Helpers\EmployeeHelper;
use Domain\Support\QueryBuilders\BaseBuilder;

class EmployeeQueryBuilder extends BaseBuilder
{
    public function helper(): string
    {
        return EmployeeHelper::class;
    }
}
