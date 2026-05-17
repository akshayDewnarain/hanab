<?php

namespace Domain\Employees\QueryBuilders;

use Domain\Employees\Helpers\EmployeeSkillHelper;
use Domain\Support\QueryBuilders\BaseBuilder;

class EmployeeSkillQueryBuilder extends BaseBuilder
{
    public function helper(): string
    {
        return EmployeeSkillHelper::class;
    }
}
