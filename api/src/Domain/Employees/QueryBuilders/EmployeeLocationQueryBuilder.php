<?php

namespace Domain\Employees\QueryBuilders;

use Domain\Employees\Helpers\EmployeeLocationHelper;
use Domain\Support\QueryBuilders\BaseBuilder;

class EmployeeLocationQueryBuilder extends BaseBuilder
{
    public function helper(): string
    {
        return EmployeeLocationHelper::class;
    }
}
