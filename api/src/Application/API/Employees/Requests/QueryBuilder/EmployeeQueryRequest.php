<?php

namespace Application\API\Employees\Requests\QueryBuilder;

use Application\API\Support\QueryBuilders\ApiQueryBuilder;
use Domain\Employees\Http\Requests\QueryBuilders\EmployeeQueryRequest as DomainEmployeeQueryRequest;

class EmployeeQueryRequest extends DomainEmployeeQueryRequest
{
    protected function queryClass(): mixed
    {
        return ApiQueryBuilder::class;
    }
}
