<?php

namespace Application\API\Employees\Requests\QueryBuilder;

use Application\API\Support\QueryBuilders\ApiQueryBuilder;
use Domain\Employees\Http\Requests\QueryBuilders\EmployeeRoleQueryRequest as DomainEmployeeRoleQueryRequest;

class EmployeeRoleQueryRequest extends DomainEmployeeRoleQueryRequest
{
    protected function queryClass(): mixed
    {
        return ApiQueryBuilder::class;
    }
}
