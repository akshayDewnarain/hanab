<?php

namespace Application\API\Employees\Requests\QueryBuilder;

use Application\API\Support\QueryBuilders\ApiQueryBuilder;
use Domain\Employees\Http\Requests\QueryBuilders\EmployeeSkillQueryRequest as DomainEmployeeSkillQueryRequest;

class EmployeeSkillQueryRequest extends DomainEmployeeSkillQueryRequest
{
    protected function queryClass(): mixed
    {
        return ApiQueryBuilder::class;
    }
}
